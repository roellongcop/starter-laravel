<?php

use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    actingAsRole('developer');
});

it('creates a role and derives module_access from permissions', function (): void {
    $this->post(route('roles.store'), [
        'name' => 'editor',
        'description' => 'Edits content',
        'permissions' => ['files.index', 'files.update', 'view-inactive'],
    ])->assertRedirect();

    $role = Role::where('name', 'editor')->first();

    expect($role->hasPermissionTo('files.update'))->toBeTrue()
        ->and($role->module_access['files'])->toEqualCanonicalizing(['index', 'update'])
        // No custom menu posted → main_navigation stays null (derived at render).
        ->and($role->main_navigation)->toBeNull();
});

it('re-derives module_access when permissions change', function (): void {
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $role->syncPermissions(['files.index']);

    $this->patch(route('roles.update', $role), [
        'name' => 'editor',
        'permissions' => ['users.index', 'users.show'],
    ])->assertRedirect();

    $role->refresh();
    expect(array_keys($role->module_access))->toBe(['users'])
        ->and($role->module_access['users'])->toEqualCanonicalizing(['index', 'show']);
});

it('blocks deleting a system role', function (): void {
    $system = Role::where('name', 'admin')->first();

    $this->delete(route('roles.destroy', $system))->assertForbidden();

    expect(Role::find($system->id))->not->toBeNull();
});

it('blocks renaming a system role', function (): void {
    $system = Role::where('name', 'admin')->first();

    $this->patch(route('roles.update', $system), [
        'name' => 'renamed-admin',
        'permissions' => [],
    ])->assertForbidden();

    expect(Role::find($system->id)->name)->toBe('admin');
});
