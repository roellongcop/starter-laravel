<?php

use App\Models\File;
use App\Models\Ip;
use App\Models\Role;
use App\Models\Theme;
use App\Models\User;
use App\Models\Visitor;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    // developer gets god-mode via Gate::before, so authorization never blocks
    // the smoke sweep — we only care that each page renders.
    actingAsRole('developer');
});

/**
 * Breadth-first smoke test: every GET route that renders an Inertia page should
 * return 200. Complements the per-feature tests that assert components/props.
 *
 * Intentionally NOT covered (not GET Inertia pages): imports.preview (parses a
 * real uploaded file), imports.errors + *.download/*.preview/media.img/avatar
 * (streamed responses), logs.show (needs a real audit row), and every
 * POST/PATCH/DELETE route.
 */
it('renders every page route with a 200', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();
    $file = File::factory()->create(['owner_id' => auth()->id()]);
    $ip = Ip::factory()->create();
    $visitor = Visitor::factory()->create();
    $role = Role::query()->firstOrFail();

    /** @var array<int, array{0: string, 1: array<int, mixed>}> $pages */
    $pages = [
        ['/', []],
        ['contact', []],
        ['dashboard', []],
        ['profile.edit', []],
        ['settings.index', []],
        ['notifications.index', []],
        ['sessions.index', []],
        ['logs.index', []],
        ['visitors.index', []],
        ['visit-logs.index', []],
        ['queue.index', []],
        ['backups.index', []],
        ['exports.index', []],
        ['exports.create', []],
        ['imports.index', []],
        ['imports.create', []],
        ['users.index', []],
        ['users.create', []],
        ['users.show', [$user]],
        ['users.edit', [$user]],
        ['roles.index', []],
        ['roles.create', []],
        ['roles.show', [$role]],
        ['roles.edit', [$role]],
        ['themes.index', []],
        ['themes.create', []],
        ['themes.show', [$theme]],
        ['themes.edit', [$theme]],
        ['files.index', []],
        ['files.create', []],
        ['files.show', [$file]],
        ['ips.index', []],
        ['ips.create', []],
        ['ips.show', [$ip]],
        ['ips.edit', [$ip]],
        ['visitors.show', [$visitor]],
    ];

    foreach ($pages as [$name, $params]) {
        $url = $name === '/' ? '/' : route($name, $params);
        $status = $this->get($url)->getStatusCode();

        $this->assertSame(200, $status, "Route [{$name}] ({$url}) returned {$status}, expected 200.");
    }
});
