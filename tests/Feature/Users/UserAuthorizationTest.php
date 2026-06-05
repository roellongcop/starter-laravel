<?php

use App\Enums\RecordStatus;
use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('redirects guests to login', function (): void {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

it('lets an admin list users but blocks deletion', function (): void {
    actingAsRole(SystemRole::Admin);
    $target = User::factory()->create();

    $this->get(route('users.index'))->assertOk();
    $this->delete(route('users.destroy', $target))->assertForbidden();
    $this->get(route('users.create'))->assertForbidden();
});

it('lets a developer delete users', function (): void {
    actingAsRole(SystemRole::Developer);
    $target = User::factory()->create();

    $this->delete(route('users.destroy', $target))->assertRedirect();
    expect(User::withInactive()->find($target->id))->toBeNull();
});

it('gates inactive rows behind view-inactive', function (): void {
    User::factory()->create(['record_status' => RecordStatus::Inactive]);

    // admin lacks view-inactive: the inactive flag is ignored.
    actingAsRole(SystemRole::Admin);
    $this->get(route('users.index', ['inactive' => 1]))
        ->assertInertia(fn ($page) => $page->where('filters.inactive', false));

    // developer has view-inactive (all permissions): inactive rows surface.
    actingAsRole(SystemRole::Developer);
    $this->get(route('users.index', ['inactive' => 1]))
        ->assertInertia(fn ($page) => $page
            ->where('filters.inactive', true)
            ->has('users.data', 1));
});
