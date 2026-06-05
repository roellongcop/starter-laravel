<?php

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('seeds the three system roles with derived module access', function (): void {
    expect(Role::pluck('name')->all())
        ->toContain(SystemRole::Developer->value)
        ->toContain(SystemRole::Superadmin->value)
        ->toContain(SystemRole::Admin->value);

    $admin = Role::where('name', SystemRole::Admin->value)->first();

    expect($admin->role_type->value)->toBe('System')
        ->and($admin->module_access['users'])->toBe(['index', 'show'])
        // Seeded roles use the permission-derived menu (main_navigation null).
        ->and($admin->main_navigation)->toBeNull();
});

it('lets the developer bypass every gate', function (): void {
    $developer = User::where('email', 'developer@developer.com')->first();

    expect($developer->hasRole(SystemRole::Developer->value))->toBeTrue()
        ->and($developer->can('users.delete'))->toBeTrue()
        ->and($developer->can('settings.update'))->toBeTrue();
});

it('grants the admin read access but denies destructive abilities', function (): void {
    $admin = User::where('email', 'admin@admin.com')->first();

    expect($admin->can('users.index'))->toBeTrue()
        ->and($admin->can('files.delete'))->toBeTrue()
        ->and($admin->can('users.delete'))->toBeFalse()
        ->and($admin->can('settings.update'))->toBeFalse();
});

it('logs in a demo user with password equal to email', function (): void {
    $response = $this->post('/login', [
        'email' => 'superadmin@superadmin.com',
        'password' => 'superadmin@superadmin.com',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    expect(auth()->user()->hasRole(SystemRole::Superadmin->value))->toBeTrue();
});
