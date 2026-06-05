<?php

use App\Enums\SystemRole;
use App\Models\Role;
use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

/** Build a custom role with a menu + permissions + priority. */
function customRole(string $name, array $permissions, ?array $menu, int $priority = 0): Role
{
    /** @var Role $role */
    $role = Role::create(['name' => $name, 'guard_name' => 'web']);
    $role->syncPermissions($permissions);
    $role->forceFill([
        'module_access' => Navigation::modulesFor($permissions),
        'main_navigation' => $menu,
        'priority' => $priority,
    ])->save();

    return $role;
}

it('persists main_navigation and priority and returns them on edit', function (): void {
    actingAsRole(SystemRole::Developer);

    $menu = [
        ['key' => 'users', 'label' => 'Members', 'icon' => 'Users', 'href' => '/users'],
        ['label' => 'Docs', 'icon' => 'Link', 'href' => 'https://example.com', 'external' => true],
    ];

    $this->post(route('roles.store'), [
        'name' => 'Editor',
        'permissions' => ['users.index'],
        'main_navigation' => $menu,
        'priority' => 15,
    ])->assertRedirect();

    $role = Role::where('name', 'Editor')->first();
    expect($role->priority)->toBe(15)
        ->and($role->main_navigation)->toBe($menu);

    $this->get(route('roles.edit', $role))
        ->assertInertia(fn ($page) => $page
            ->where('role.priority', 15)
            ->has('role.main_navigation', 2)
            ->has('menuCatalog'));
});

it('rejects a menu link with an unsafe href scheme', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->post(route('roles.store'), [
        'name' => 'Bad',
        'permissions' => [],
        'main_navigation' => [
            ['label' => 'Evil', 'href' => 'javascript:alert(1)'],
        ],
    ])->assertSessionHasErrors('main_navigation.0.href');
});

it('derives the default menu for a role without a custom one', function (): void {
    $role = customRole('Viewer', ['users.index', 'roles.index'], null);
    $user = User::factory()->create();
    $user->assignRole($role);

    $nav = Navigation::forUser($user);
    $labels = collect($nav)->pluck('label');

    expect($labels)->toContain('Access'); // derived group containing users/roles
});

it('merges two roles\' custom menus, deduped by group', function (): void {
    $a = customRole('A', ['users.index'], [
        ['label' => 'Access', 'icon' => 'ShieldCheck', 'children' => [
            ['key' => 'users', 'label' => 'Users', 'icon' => 'Users', 'href' => '/users'],
        ]],
    ], priority: 10);
    $b = customRole('B', ['roles.index'], [
        ['label' => 'Access', 'icon' => 'ShieldCheck', 'children' => [
            ['key' => 'roles', 'label' => 'Roles', 'icon' => 'KeyRound', 'href' => '/roles'],
        ]],
    ], priority: 5);

    $user = User::factory()->create();
    $user->assignRole($a, $b);

    $nav = Navigation::forUser($user);

    expect($nav)->toHaveCount(1)
        ->and($nav[0]['label'])->toBe('Access')
        ->and(collect($nav[0]['children'])->pluck('key')->all())
        ->toEqual(['users', 'roles']);
});

it('drops a module the user cannot access but keeps an external link', function (): void {
    // Role lists a "users" module + an external link, but lacks users permission.
    $role = customRole('Linky', [], [
        ['key' => 'users', 'label' => 'Users', 'icon' => 'Users', 'href' => '/users'],
        ['label' => 'Docs', 'icon' => 'Link', 'href' => 'https://example.com', 'external' => true],
    ]);
    $user = User::factory()->create();
    $user->assignRole($role);

    $nav = Navigation::forUser($user);
    $labels = collect($nav)->pluck('label');

    expect($labels)->toContain('Docs')
        ->and($labels)->not->toContain('Users');
});

it('lets the higher-priority role win a leaf label conflict', function (): void {
    $high = customRole('High', ['users.index'], [
        ['key' => 'users', 'label' => 'Members', 'icon' => 'Users', 'href' => '/users'],
    ], priority: 20);
    $low = customRole('Low', ['users.index'], [
        ['key' => 'users', 'label' => 'Users', 'icon' => 'Users', 'href' => '/users'],
    ], priority: 10);

    $user = User::factory()->create();
    $user->assignRole($high, $low);

    $nav = Navigation::forUser($user);

    expect($nav)->toHaveCount(1)
        ->and($nav[0]['label'])->toBe('Members');
});
