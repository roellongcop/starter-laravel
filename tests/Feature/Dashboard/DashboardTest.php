<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('renders the dashboard with metrics', function (): void {
    actingAsRole('developer');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('metrics')
            ->has('recent.users'));
});

it('gates metric tiles by permission', function (): void {
    // A role with only users.index should see just the Users tile + the always-on
    // unread-alerts tile = 2.
    $role = Role::create(['name' => 'limited', 'guard_name' => 'web']);
    $role->syncPermissions(['dashboard.index', 'users.index']);

    $user = User::factory()->create();
    $user->assignRole('limited');
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('metrics', 2));
});

it('returns grouped search hits', function (): void {
    actingAsRole('developer');
    User::factory()->create(['name' => 'Searchable Sam', 'email' => 'sam@example.com']);

    $response = $this->getJson(route('dashboard.search', ['q' => 'Searchable']));

    $response->assertOk();
    expect($response->json('groups.0.label'))->toBe('Users')
        ->and($response->json('groups.0.hits.0.label'))->toBe('Searchable Sam');
});

it('redirects guests from the dashboard', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('forbids a user without dashboard access', function (): void {
    $this->actingAs(User::factory()->create());
    $this->get(route('dashboard'))->assertForbidden();
});
