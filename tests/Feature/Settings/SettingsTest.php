<?php

use App\Providers\AppServiceProvider;
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

it('applies stored timezone and pagination_size to config at boot', function (): void {
    $system = app(SystemSettings::class);
    $system->timezone = 'Asia/Manila';
    $system->pagination_size = 7;
    $system->save();

    // The provider captures settings at boot, before this test changed them, so
    // re-run boot() to pick up the freshly saved values.
    (new AppServiceProvider(app()))->boot();

    expect(config('app.timezone'))->toBe('Asia/Manila')
        ->and(config('keen.pagination_size'))->toBe(7);
});

it('exposes auto_logout_seconds in the shared inertia props', function (): void {
    actingAsRole('developer');

    $system = app(SystemSettings::class);
    $system->auto_logout_seconds = 120;
    $system->save();

    $this->get(route('settings.index'))
        ->assertInertia(fn ($page) => $page
            ->where('settings.system.auto_logout_seconds', 120));
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
