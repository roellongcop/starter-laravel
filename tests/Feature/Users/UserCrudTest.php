<?php

use App\Enums\SystemRole;
use App\Models\User;
use App\Models\UserMeta;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use OwenIt\Auditing\Models\Audit;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    actingAsRole(SystemRole::Developer);
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
        'roles' => [SystemRole::Admin->value],
        'meta' => [['key' => 'rank', 'value' => 'Rear Admiral']],
    ]);

    $user = User::where('email', 'grace@example.com')->first();

    $response->assertRedirect(route('users.show', $user));
    expect($user->hasRole(SystemRole::Admin->value))->toBeTrue()
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

it('does not audit unchanged meta on save', function (): void {
    // owen-it skips auditing under `php artisan test` unless console auditing is on.
    config(['audit.console' => true]);

    $user = User::factory()->create();
    $user->meta()->create(['key' => 'rank', 'value' => 'Captain']);

    $metaAudits = fn (): int => Audit::where('auditable_type', UserMeta::class)->count();
    $before = $metaAudits();

    // Re-submit the identical meta — nothing about the custom field changed.
    $this->patch(route('users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'user_status' => 'Active',
        'roles' => [],
        'meta' => [['key' => 'rank', 'value' => 'Captain']],
    ])->assertRedirect(route('users.show', $user));

    expect($metaAudits())->toBe($before);

    // Actually changing the value records exactly one `updated` audit.
    $this->patch(route('users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'user_status' => 'Active',
        'roles' => [],
        'meta' => [['key' => 'rank', 'value' => 'Rear Admiral']],
    ])->assertRedirect(route('users.show', $user));

    expect($metaAudits())->toBe($before + 1)
        ->and(Audit::where('auditable_type', UserMeta::class)->latest('id')->value('event'))->toBe('updated')
        ->and($user->meta()->where('key', 'rank')->value('value'))->toBe('Rear Admiral');
});

it('audits role changes on a user', function (): void {
    // owen-it skips auditing under `php artisan test` unless console auditing is on.
    config(['audit.console' => true]);

    $user = User::factory()->create();
    $user->assignRole(SystemRole::Admin->value);

    $roleAudits = fn () => Audit::where('auditable_type', User::class)
        ->where('event', 'roles-synced');

    // Changing the role set records one `roles-synced` audit with old/new roles.
    $this->patch(route('users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'user_status' => 'Active',
        'roles' => [SystemRole::User->value],
        'meta' => [],
    ])->assertRedirect(route('users.show', $user));

    $audit = $roleAudits()->latest('id')->first();
    expect($roleAudits()->count())->toBe(1)
        ->and($audit->old_values)->toBe(['roles' => [SystemRole::Admin->value]])
        ->and($audit->new_values)->toBe(['roles' => [SystemRole::User->value]]);

    // Re-submitting the same role set adds no further audit.
    $this->patch(route('users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'user_status' => 'Active',
        'roles' => [SystemRole::User->value],
        'meta' => [],
    ])->assertRedirect(route('users.show', $user));

    expect($roleAudits()->count())->toBe(1);
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

    $this->post(route('users.bulk'), ['process' => 'in_active', 'tokens' => [$a->token, $b->token]])
        ->assertRedirect();

    expect(User::find($a->id))->toBeNull()
        ->and(User::onlyInactive()->whereIn('id', [$a->id, $b->id])->count())->toBe(2);
});
