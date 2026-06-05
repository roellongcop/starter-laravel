<?php

use App\Actions\StoreUploadedFile;
use App\Enums\SystemRole;
use App\Settings\ImageSettings;
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

it('saves the brand image tokens from the image tab', function (): void {
    $user = actingAsRole(SystemRole::Developer);
    $favicon = app(StoreUploadedFile::class)(UploadedFile::fake()->image('f.png', 64, 64), $user->id);
    $square = app(StoreUploadedFile::class)(UploadedFile::fake()->image('s.png', 256, 256), $user->id);

    $this->put(route('settings.update', 'image'), [
        'favicon_token' => $favicon->token,
        'square_logo_token' => $square->token,
        'landscape_logo_token' => null,
    ])->assertRedirect()->assertSessionHas('success');

    $image = app(ImageSettings::class);
    expect($image->favicon_token)->toBe($favicon->token)
        ->and($image->square_logo_token)->toBe($square->token)
        ->and($image->landscape_logo_token)->toBeNull();
});

it('rejects a brand token that is not a real file', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->put(route('settings.update', 'image'), [
        'favicon_token' => 'not-a-real-token',
        'square_logo_token' => null,
        'landscape_logo_token' => null,
    ])->assertSessionHasErrors('favicon_token');
});

it('serves a configured brand image publicly (no auth)', function (): void {
    $user = actingAsRole(SystemRole::Developer);
    $file = app(StoreUploadedFile::class)(UploadedFile::fake()->image('logo.png', 300, 300), $user->id);

    $image = app(ImageSettings::class);
    $image->square_logo_token = $file->token;
    $image->save();

    // Public route — no acting user.
    auth()->logout();
    $this->get(route('brand.show', ['slot' => 'square-logo']))
        ->assertOk()
        ->assertHeader('content-type', $file->mime);
});

it('returns 404 for an unset brand slot', function (): void {
    $this->get(route('brand.show', ['slot' => 'favicon']))->assertNotFound();
});

it('rejects an unknown brand slot', function (): void {
    $this->get('/brand/bogus')->assertNotFound();
});

it('shares brand asset urls as a global prop', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('brand.favicon_url')
            ->has('brand.square_logo_url')
            ->has('brand.landscape_logo_url'));
});
