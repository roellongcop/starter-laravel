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
    Storage::fake('image-cache');
});

it('uploads an image to a year/month path with a random unique name', function (): void {
    $user = actingAsRole('developer');

    $response = $this->post(route('media.store'), [
        'file' => UploadedFile::fake()->image('My Photo.jpg', 300, 300),
    ]);

    $response->assertOk()->assertJsonStructure(['token', 'url', 'thumb_url']);

    $file = File::where('token', $response->json('token'))->first();
    expect($file->owner_id)->toBe($user->id)
        ->and($file->original_name)->toBe('My Photo.jpg')
        ->and($file->path)->toMatch('#^\d{4}/\d{2}/[A-Za-z0-9]{40}\.\w+$#');
});

it('serves a resized cached copy of an image', function (): void {
    $user = actingAsRole('developer');
    $file = app(StoreUploadedFile::class)(
        UploadedFile::fake()->image('p.jpg', 600, 400),
        $user->id,
    );

    $this->get(route('media.img', ['file' => $file->token, 'w' => 200]))
        ->assertOk()
        ->assertHeader('content-type', $file->mime);
});

it('clamps an out-of-range width instead of failing', function (): void {
    $user = actingAsRole('developer');
    $file = app(StoreUploadedFile::class)(
        UploadedFile::fake()->image('p.jpg', 600, 400),
        $user->id,
    );

    $this->get(route('media.img', ['file' => $file->token, 'w' => 99999]))
        ->assertOk();
});

it('forbids viewing an image you do not own without files.view', function (): void {
    $owner = User::factory()->create();
    $file = app(StoreUploadedFile::class)(
        UploadedFile::fake()->image('p.jpg', 300, 300),
        $owner->id,
    );

    $this->actingAs(User::factory()->create())
        ->get(route('media.img', ['file' => $file->token, 'w' => 200]))
        ->assertForbidden();
});

it('rejects a non-image upload', function (): void {
    actingAsRole('developer');

    $this->post(route('media.store'), [
        'file' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
    ])->assertSessionHasErrors('file');
});
