<?php

use App\Enums\SystemRole;
use App\Models\File;
use App\Models\Organization;
use App\Models\ReferenceFile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('creates a reference without a file', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('reference-files.store'), [
        'name' => 'Handbook',
        'description' => 'The company handbook',
        'organization' => $organization->token,
    ])->assertRedirect(route('reference-files.index'));

    $reference = ReferenceFile::where('name', 'Handbook')->first();
    expect($reference)->not->toBeNull()
        ->and($reference->organization_id)->toBe($organization->id)
        ->and($reference->file_id)->toBeNull();
});

it('requires a name and a valid organization', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->post(route('reference-files.store'), [
        'organization' => 'not-a-real-token',
    ])->assertSessionHasErrors(['name', 'organization']);
});

it('rejects a duplicate reference name within the same organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    ReferenceFile::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Policy',
    ]);

    $this->post(route('reference-files.store'), [
        'name' => 'Policy',
        'organization' => $organization->token,
    ])->assertSessionHasErrors('name');

    expect(ReferenceFile::where('name', 'Policy')->count())->toBe(1);
});

it('allows the same reference name across different organizations', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    ReferenceFile::factory()->create(['organization_id' => $orgA->id, 'name' => 'Shared']);

    $this->post(route('reference-files.store'), [
        'name' => 'Shared',
        'organization' => $orgB->token,
    ])->assertRedirect(route('reference-files.index'));

    expect(ReferenceFile::where('name', 'Shared')->count())->toBe(2);
});

it('uploads a file and returns its token', function (): void {
    Storage::fake('uploads');
    actingAsRole(SystemRole::Developer);

    $this->post(route('reference-files.upload'), [
        'file' => UploadedFile::fake()->create('handbook.pdf', 120, 'application/pdf'),
    ])->assertOk()->assertJsonStructure(['token', 'name', 'size', 'extension']);

    expect(File::where('original_name', 'handbook.pdf')->exists())->toBeTrue();
});

it('attaches an uploaded file to a reference and resolves the token to an id', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $file = File::factory()->create();

    $this->post(route('reference-files.store'), [
        'name' => 'With File',
        'organization' => $organization->token,
        'file_token' => $file->token,
    ])->assertRedirect(route('reference-files.index'));

    expect(ReferenceFile::where('name', 'With File')->first()->file_id)->toBe($file->id);
});

it('replaces the attached file on update and deletes the previous one', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $oldFile = File::factory()->create();
    $newFile = File::factory()->create();
    $reference = ReferenceFile::factory()->create([
        'organization_id' => $organization->id,
        'file_id' => $oldFile->id,
    ]);

    $this->patch(route('reference-files.update', $reference), [
        'name' => $reference->name,
        'organization' => $organization->token,
        'file_token' => $newFile->token,
    ])->assertRedirect();

    expect($reference->fresh()->file_id)->toBe($newFile->id)
        ->and(File::withInactive()->find($oldFile->id))->toBeNull();
});

it('deletes a reference and its attached file', function (): void {
    actingAsRole(SystemRole::Developer);
    $file = File::factory()->create();
    $reference = ReferenceFile::factory()->create(['file_id' => $file->id]);

    $this->delete(route('reference-files.destroy', $reference))->assertRedirect();

    expect(ReferenceFile::withInactive()->find($reference->id))->toBeNull()
        ->and(File::withInactive()->find($file->id))->toBeNull();
});

it('streams the attached file as a gated download', function (): void {
    Storage::fake('uploads');
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $token = $this->post(route('reference-files.upload'), [
        'file' => UploadedFile::fake()->create('doc.pdf', 80, 'application/pdf'),
    ])->json('token');

    $reference = ReferenceFile::factory()->create([
        'organization_id' => $organization->id,
        'file_id' => File::where('token', $token)->value('id'),
    ]);

    $this->get(route('reference-files.download', $reference))->assertOk();
});

it('returns 404 downloading a reference with no file', function (): void {
    actingAsRole(SystemRole::Developer);
    $reference = ReferenceFile::factory()->create(['file_id' => null]);

    $this->get(route('reference-files.download', $reference))->assertNotFound();
});

it('never leaks the organization or file id to the frontend', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $file = File::factory()->create();
    $reference = ReferenceFile::factory()->create([
        'organization_id' => $organization->id,
        'file_id' => $file->id,
    ]);

    $this->get(route('reference-files.show', $reference))
        ->assertInertia(fn ($page) => $page
            ->where('reference.organization', $organization->token)
            ->where('reference.file_token', $file->token)
            ->missing('reference.organization_id')
            ->missing('reference.file_id'));
});

it('filters the index by organization token', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    ReferenceFile::factory()->count(2)->create(['organization_id' => $orgA->id]);
    ReferenceFile::factory()->create(['organization_id' => $orgB->id]);

    $this->get(route('reference-files.index', ['organization' => $orgA->token]))
        ->assertInertia(fn ($page) => $page
            ->component('ReferenceFiles/Index')
            ->has('references.data', 2)
            ->where('filters.organization', $orgA->token));
});

it('forbids reference access without permission', function (): void {
    $this->get(route('reference-files.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('reference-files.index'))->assertForbidden();
});
