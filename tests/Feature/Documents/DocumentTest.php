<?php

use App\Actions\StoreUploadedFile;
use App\Models\File;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    Storage::fake('uploads');
});

it('uploads a pdf document for the caller', function (): void {
    $user = actingAsRole('developer');

    $response = $this->postJson(route('documents.store'), [
        'file' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
    ]);

    $response->assertOk()->assertJsonStructure(['id', 'name', 'url', 'size', 'extension']);

    $file = File::find($response->json('id'));
    expect($file->owner_id)->toBe($user->id)
        ->and($file->extension)->toBe('pdf');
});

it('accepts a docx document', function (): void {
    actingAsRole('developer');

    $this->postJson(route('documents.store'), [
        'file' => UploadedFile::fake()->create(
            'report.docx',
            200,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ),
    ])->assertOk();
});

it('rejects a disallowed file type', function (): void {
    actingAsRole('developer');

    $this->postJson(route('documents.store'), [
        'file' => UploadedFile::fake()->image('photo.png', 100, 100),
    ])->assertStatus(422);

    expect(File::count())->toBe(0);
});

it('downloads a document for its owner', function (): void {
    $user = actingAsRole('developer');
    $doc = app(StoreUploadedFile::class)(
        UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
        $user->id,
        'document',
    );

    $this->get(route('documents.download', $doc))->assertOk();
});

it('streams a document inline for the in-app viewer', function (): void {
    $user = actingAsRole('developer');
    $doc = app(StoreUploadedFile::class)(
        UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
        $user->id,
        'document',
    );

    $response = $this->get(route('documents.view', $doc));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('inline');
});

it('forbids viewing a document you do not own without access', function (): void {
    $owner = User::factory()->create();
    $doc = app(StoreUploadedFile::class)(
        UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
        $owner->id,
        'document',
    );

    $this->actingAs(User::factory()->create())
        ->get(route('documents.view', $doc))
        ->assertForbidden();
});

it('forbids downloading a document you do not own without files.view', function (): void {
    $owner = User::factory()->create();
    $doc = app(StoreUploadedFile::class)(
        UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
        $owner->id,
        'document',
    );

    $this->actingAs(User::factory()->create())
        ->get(route('documents.download', $doc))
        ->assertForbidden();
});

it('deletes the caller\'s document', function (): void {
    $user = actingAsRole('developer');
    $doc = app(StoreUploadedFile::class)(
        UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
        $user->id,
        'document',
    );

    $this->delete(route('documents.destroy', $doc))->assertRedirect();

    expect(File::find($doc->id))->toBeNull();
});

it('shares only the caller\'s documents on the profile page', function (): void {
    $user = actingAsRole('developer');
    File::factory()->count(2)->create(['owner_id' => $user->id, 'extension' => 'pdf']);
    File::factory()->create(['owner_id' => $user->id, 'extension' => 'png']); // image, excluded
    File::factory()->create(['owner_id' => User::factory()->create()->id, 'extension' => 'pdf']);

    $this->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page->has('documents.data', 2));
});

it('lets an admin upload a document for another user', function (): void {
    actingAsRole('developer');
    $target = User::factory()->create();

    $response = $this->postJson(route('documents.store'), [
        'file' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
        'user_id' => $target->id,
    ]);

    $response->assertOk();
    expect(File::find($response->json('id'))->owner_id)->toBe($target->id);
});

it('forbids uploading a document for another user without users.update', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('admin'); // index/show only, no users.update
    $this->actingAs($actor);
    $target = User::factory()->create();

    $this->postJson(route('documents.store'), [
        'file' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
        'user_id' => $target->id,
    ])->assertForbidden();
});

it('shares the target user\'s documents on the admin show and edit pages', function (): void {
    actingAsRole('developer');
    $target = User::factory()->create();
    File::factory()->count(2)->create(['owner_id' => $target->id, 'extension' => 'pdf']);
    File::factory()->create(['owner_id' => $target->id, 'extension' => 'png']); // image, excluded

    $this->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page->has('documents.data', 2));

    $this->get(route('users.edit', $target))
        ->assertInertia(fn ($page) => $page->has('documents.data', 2));
});
