<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    actingAsRole('developer');
});

it('lists users with the keyset cursor shape', function (): void {
    User::factory()->count(3)->create();

    $this->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Index')
            ->has('users.data')
            ->has('users.next_cursor')
            ->has('users.prev_cursor')
            ->where('users.has_more', false));
});

it('creates a user with roles and inline meta', function (): void {
    $response = $this->post(route('users.store'), [
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
        'username' => 'grace',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'user_status' => 'Active',
        'roles' => ['admin'],
        'meta' => [['key' => 'rank', 'value' => 'Rear Admiral']],
    ]);

    $user = User::where('email', 'grace@example.com')->first();

    $response->assertRedirect(route('users.show', $user));
    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->meta()->where('key', 'rank')->value('value'))->toBe('Rear Admiral');
});

it('updates a user and re-syncs meta', function (): void {
    $user = User::factory()->create();
    $user->meta()->create(['key' => 'old', 'value' => 'gone']);

    $this->patch(route('users.update', $user), [
        'name' => 'Renamed',
        'email' => $user->email,
        'user_status' => 'Blocked',
        'roles' => [],
        'meta' => [['key' => 'new', 'value' => 'kept']],
    ])->assertRedirect(route('users.show', $user));

    $user->refresh();
    expect($user->name)->toBe('Renamed')
        ->and($user->user_status->value)->toBe('Blocked')
        ->and($user->meta()->pluck('key')->all())->toBe(['new']);
});

it('searches users by name/email', function (): void {
    User::factory()->create(['name' => 'Findme Person', 'email' => 'find@example.com']);
    User::factory()->create(['name' => 'Other', 'email' => 'other@example.com']);

    $this->get(route('users.index', ['search' => 'Findme']))
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

it('deletes a user and returns to the filtered list', function (): void {
    $user = User::factory()->create();
    $from = route('users.index', ['inactive' => 1, 'search' => 'foo']);

    $this->from($from)
        ->delete(route('users.destroy', $user))
        ->assertRedirect($from); // filters preserved, not a bare /users

    expect(User::withInactive()->find($user->id))->toBeNull();
});

it('can view, edit and delete an inactive user (binding lifts the active scope)', function (): void {
    $user = User::factory()->create();
    $user->inactivate();

    // The active global scope still hides it from default queries…
    expect(User::find($user->id))->toBeNull();

    // …but route-model binding resolves it for admin CRUD.
    $this->get(route('users.show', $user))->assertOk();
    $this->get(route('users.edit', $user))->assertOk();

    $this->patch(route('users.update', $user), [
        'name' => 'Reactivated',
        'email' => $user->email,
        'user_status' => 'Active',
        'roles' => [],
    ])->assertRedirect(route('users.show', $user));

    $this->delete(route('users.destroy', $user))->assertRedirect();
    expect(User::withInactive()->find($user->id))->toBeNull();
});

it('deletes from the show page and returns to the index (not the gone page)', function (): void {
    $user = User::factory()->create();

    $this->from(route('users.show', $user))
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));
});

it('runs bulk inactivate which hides rows from the default scope', function (): void {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->post(route('users.bulk'), ['process' => 'in_active', 'ids' => [$a->id, $b->id]])
        ->assertRedirect();

    expect(User::find($a->id))->toBeNull()
        ->and(User::onlyInactive()->whereIn('id', [$a->id, $b->id])->count())->toBe(2);
});
