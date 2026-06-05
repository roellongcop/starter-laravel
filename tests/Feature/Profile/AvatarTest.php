<?php

use App\Actions\StoreUploadedFile;
use App\Enums\SystemRole;
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

/** Create a real, media-backed image File owned by $user. */
function imageFileFor(User $user): File
{
    return app(StoreUploadedFile::class)(
        UploadedFile::fake()->image('photo.jpg', 300, 300),
        $user->id,
        'avatar',
    );
}

it('sets the avatar from an existing owned image', function (): void {
    $user = actingAsRole(SystemRole::Developer);
    $file = imageFileFor($user);

    $this->post(route('profile.avatar.store'), ['file_token' => $file->token])
        ->assertRedirect();

    expect($user->refresh()->avatar_file_id)->toBe($file->id)
        ->and($user->avatar_url)->not->toBeNull();
});

it('rejects an image owned by someone else', function (): void {
    actingAsRole(SystemRole::Developer);
    $file = imageFileFor(User::factory()->create());

    $this->post(route('profile.avatar.store'), ['file_token' => $file->token])
        ->assertSessionHasErrors('file_token');
});

it('rejects a file that is not an image', function (): void {
    $user = actingAsRole(SystemRole::Developer);
    $file = File::factory()->create(['owner_id' => $user->id, 'mime' => 'application/pdf']);

    $this->post(route('profile.avatar.store'), ['file_token' => $file->token])
        ->assertSessionHasErrors('file_token');
});

it('streams the avatar (resized) to another authenticated user', function (): void {
    $owner = actingAsRole(SystemRole::Developer);
    $owner->update(['avatar_file_id' => imageFileFor($owner)->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('profile.avatar', ['user' => $owner, 'w' => 64]))
        ->assertOk();
});

it('returns 404 when the user has no avatar', function (): void {
    $user = actingAsRole(SystemRole::Developer);

    $this->get(route('profile.avatar', $user))->assertNotFound();
});

it('clears the avatar', function (): void {
    $user = actingAsRole(SystemRole::Developer);
    $user->update(['avatar_file_id' => imageFileFor($user)->id]);

    $this->delete(route('profile.avatar.destroy'))->assertRedirect();

    expect($user->refresh()->avatar_file_id)->toBeNull();
});

it('lists only the caller\'s own images in the picker', function (): void {
    $user = actingAsRole(SystemRole::Developer);
    File::factory()->count(2)->create(['owner_id' => $user->id, 'mime' => 'image/png']);
    File::factory()->create(['owner_id' => $user->id, 'mime' => 'application/pdf']);
    File::factory()->create(['owner_id' => User::factory()->create()->id, 'mime' => 'image/png']);

    $this->getJson(route('profile.photos'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('lets an admin set another user\'s avatar via the users form', function (): void {
    $admin = actingAsRole(SystemRole::Developer);
    $target = User::factory()->create()->refresh();
    $file = imageFileFor($admin);

    $this->patch(route('users.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'user_status' => $target->user_status->value,
        'avatar_file_token' => $file->token,
    ])->assertRedirect();

    expect($target->refresh()->avatar_file_id)->toBe($file->id);
});
