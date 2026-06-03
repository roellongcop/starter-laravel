<?php

use App\Settings\SystemSettings;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('renders all settings groups', function (): void {
    actingAsRole('developer');

    $this->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('settings.system')
            ->has('settings.email')
            ->has('settings.image')
            ->has('settings.notification'));
});

it('persists a system settings update', function (): void {
    actingAsRole('developer');

    $this->put(route('settings.update', 'system'), [
        'app_name' => 'Renamed App',
        'timezone' => 'UTC',
        'pagination_size' => 30,
        'auto_logout_seconds' => 0,
        'enable_visitor' => true,
        'whitelist_ip_only' => false,
        'default_theme' => 'dark',
    ])->assertRedirect();

    $system = app(SystemSettings::class);
    expect($system->app_name)->toBe('Renamed App')
        ->and($system->pagination_size)->toBe(30)
        ->and($system->default_theme)->toBe('dark');
});

it('validates settings input', function (): void {
    actingAsRole('developer');

    $this->put(route('settings.update', 'system'), [
        'app_name' => '',
        'timezone' => 'Not/AZone',
        'pagination_size' => 0,
        'auto_logout_seconds' => -5,
        'enable_visitor' => true,
        'whitelist_ip_only' => false,
        'default_theme' => 'neon',
    ])->assertSessionHasErrors(['app_name', 'timezone', 'pagination_size', 'auto_logout_seconds', 'default_theme']);
});

it('forbids updating settings without the permission', function (): void {
    // admin has settings.index but not settings.update.
    actingAsRole('admin');

    $this->get(route('settings.index'))->assertOk();
    $this->put(route('settings.update', 'system'), [
        'app_name' => 'Nope',
        'timezone' => 'UTC',
        'pagination_size' => 20,
        'auto_logout_seconds' => 0,
        'enable_visitor' => true,
        'whitelist_ip_only' => false,
        'default_theme' => 'system',
    ])->assertForbidden();
});
