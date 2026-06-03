<?php

use App\Models\Visitor;
use App\Settings\SystemSettings;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('tracks a visitor when enabled', function (): void {
    // enable_visitor defaults to true via the seeded settings.
    $this->get('/login')->assertOk();

    expect(Visitor::count())->toBe(1)
        ->and(Visitor::first()->logs()->count())->toBeGreaterThanOrEqual(1);
});

it('does not track when disabled', function (): void {
    $settings = app(SystemSettings::class);
    $settings->enable_visitor = false;
    $settings->save();

    $this->get('/login')->assertOk();

    expect(Visitor::count())->toBe(0);
});

it('lists visitors for an authorized user', function (): void {
    // Disable live tracking so the rendered list is deterministic.
    $settings = app(SystemSettings::class);
    $settings->enable_visitor = false;
    $settings->save();

    actingAsRole('developer');
    Visitor::factory()->count(2)->create();

    $this->get(route('visitors.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Visitors/Index')->has('visitors.data', 2));
});
