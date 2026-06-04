<?php

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

it('uploads a file to the private disk and denormalizes metadata', function (): void {
    actingAsRole('developer');

    $this->post(route('files.store'), [
        'file' => UploadedFile::fake()->image('avatar.png', 100, 100),
        'tag' => 'avatar',
    ])->assertRedirect();

    $file = File::first();
    expect($file)->not->toBeNull()
        ->and($file->disk)->toBe('uploads')
        ->and($file->extension)->toBe('png')
        ->and($file->mime)->toContain('image')
        ->and($file->size)->toBeGreaterThan(0)
        ->and($file->tag)->toBe('avatar')
        ->and($file->getFirstMedia(File::COLLECTION))->not->toBeNull();
});

it('accepts document and spreadsheet types and returns JSON for axios uploads', function (): void {
    actingAsRole('developer');

    $this->postJson(route('files.store'), [
        'file' => UploadedFile::fake()->create('report.xlsx', 20),
        'tag' => 'reports',
    ])->assertOk()->assertJsonStructure(['id', 'original_name', 'extension']);

    $file = File::first();
    expect($file)->not->toBeNull()
        ->and($file->extension)->toBe('xlsx')
        ->and($file->tag)->toBe('reports');
});

it('rejects a disallowed extension', function (): void {
    actingAsRole('developer');

    $this->post(route('files.store'), [
        'file' => UploadedFile::fake()->create('malware.exe', 10),
    ])->assertSessionHasErrors('file');

    expect(File::count())->toBe(0);
});

it('streams a gated download and forbids unauthorized users', function (): void {
    actingAsRole('developer');
    $this->post(route('files.store'), [
        'file' => UploadedFile::fake()->image('pic.png'),
    ]);
    $file = File::first();

    $this->get(route('files.download', $file))->assertOk();

    // a user with no roles cannot download.
    $this->actingAs(User::factory()->create());
    $this->get(route('files.download', $file))->assertForbidden();

    // guest is redirected.
    auth()->logout();
    $this->get(route('files.download', $file))->assertRedirect(route('login'));
});

it('deletes a file and its media', function (): void {
    actingAsRole('developer');
    $this->post(route('files.store'), [
        'file' => UploadedFile::fake()->image('gone.png'),
    ]);
    $file = File::first();

    $this->delete(route('files.destroy', $file))->assertRedirect();
    expect(File::withInactive()->find($file->id))->toBeNull();
});
