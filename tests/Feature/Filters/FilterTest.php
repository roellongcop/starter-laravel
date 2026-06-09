<?php

use App\Enums\SystemRole;
use App\Models\LoginHistory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    actingAsRole(SystemRole::Developer);
});

it('escapes LIKE wildcards so search matches literally', function (): void {
    // The underscore is a LIKE wildcard. Unescaped, "a_b" would also match "axb".
    User::factory()->create(['name' => 'a_b', 'email' => 'one@example.com', 'username' => 'one']);
    User::factory()->create(['name' => 'axb', 'email' => 'two@example.com', 'username' => 'two']);

    $this->get(route('users.index', ['search' => 'a_b']))
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'a_b'));
});

it('echoes every filter key with defaults when no params are sent', function (): void {
    $this->get(route('users.index'))
        ->assertInertia(fn ($page) => $page
            ->where('filters.search', '')
            ->where('filters.inactive', false)
            ->where('filters.date_from', '')
            ->where('filters.date_to', ''));
});

it('filters users by a date range bound', function (): void {
    $old = User::factory()->create(['created_at' => '2000-01-01 00:00:00']);
    User::factory()->create(['created_at' => '2026-01-01 00:00:00']);

    // date_to excludes everything created after the bound (recent users + the
    // acting developer), leaving only the old row.
    $this->get(route('users.index', ['date_to' => '2000-06-01']))
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.token', $old->token));
});

it('rejects an inverted date range', function (): void {
    $this->get(route('users.index', ['date_from' => '2026-01-01', 'date_to' => '2020-01-01']))
        ->assertStatus(302)
        ->assertSessionHasErrors('date_to');
});

it('searches login history across a related user and an own column', function (): void {
    $zaphod = User::factory()->create(['name' => 'Zaphod']);
    LoginHistory::factory()->for($zaphod)->login()->create(['ip_address' => '10.11.12.13']);

    $trillian = User::factory()->create(['name' => 'Trillian']);
    LoginHistory::factory()->for($trillian)->login()->create(['ip_address' => '10.99.99.99']);

    // Matches via the related user's name.
    $this->get(route('login-history.index', ['search' => 'Zaphod']))
        ->assertInertia(fn ($page) => $page->has('history.data', 1));

    // Matches via the row's own ip_address column.
    $this->get(route('login-history.index', ['search' => '10.11.12.13']))
        ->assertInertia(fn ($page) => $page->has('history.data', 1));
});

it('ignores an out-of-range exact filter value', function (): void {
    LoginHistory::factory()->login()->create();
    LoginHistory::factory()->logout()->create();

    // A value outside the allow-list is a no-op: all rows return.
    $this->get(route('login-history.index', ['event' => 'garbage']))
        ->assertInertia(fn ($page) => $page->has('history.data', 2));

    // A valid value filters.
    $this->get(route('login-history.index', ['event' => 'login']))
        ->assertInertia(fn ($page) => $page->has('history.data', 1));
});
