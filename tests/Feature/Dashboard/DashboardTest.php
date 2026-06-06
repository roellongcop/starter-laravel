<?php

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Testing\TestResponse;
use Inertia\Inertia;
use Tests\TestCase;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

/**
 * Fetch a single deferred prop via an Inertia partial reload. Primes the asset
 * version with a full visit first, so the partial GET doesn't 409 on a version
 * mismatch.
 *
 * @return TestResponse
 */
function partialReload(TestCase $test, string $route, string $component, string $prop)
{
    $test->get($route)->assertOk();

    return $test->get($route, [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) Inertia::getVersion(),
        'X-Inertia-Partial-Component' => $component,
        'X-Inertia-Partial-Data' => $prop,
    ]);
}

it('renders the dashboard with recent users and defers the metrics', function (): void {
    actingAsRole(SystemRole::Developer);

    // metrics is a deferred prop: absent from the initial render, recent is inline.
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->missing('metrics')
            ->has('recent.users'));
});

it('resolves the deferred metrics on a partial reload', function (): void {
    actingAsRole(SystemRole::Developer);

    // The partial reload returns the Inertia page object as JSON; assertInertia
    // only parses the full HTML page, so assert on the JSON props directly.
    partialReload($this, route('dashboard'), 'Dashboard', 'metrics')
        ->assertOk()
        ->assertJsonPath('component', 'Dashboard')
        ->assertJsonStructure(['props' => ['metrics']]);
});

it('gates metric tiles by permission', function (): void {
    // A role with only users.index should see just the Users tile + the always-on
    // unread-alerts tile = 2. Metrics is deferred, so fetch it via a partial reload.
    $role = Role::create(['name' => 'limited', 'guard_name' => 'web']);
    $role->syncPermissions(['dashboard.index', 'users.index']);

    $user = User::factory()->create();
    $user->assignRole('limited');
    $this->actingAs($user);

    partialReload($this, route('dashboard'), 'Dashboard', 'metrics')
        ->assertOk()
        ->assertJsonCount(2, 'props.metrics');
});

it('returns grouped search hits', function (): void {
    actingAsRole(SystemRole::Developer);
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
