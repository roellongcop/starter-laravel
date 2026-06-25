<?php

use App\Enums\SystemRole;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('creates an organization role and resolves the organization token to an id', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('organization-roles.store'), [
        'name' => 'Project Manager',
        'description' => 'Runs projects',
        'organization' => $organization->token,
    ])->assertRedirect(route('organization-roles.index'));

    $role = OrganizationRole::where('name', 'Project Manager')->first();
    expect($role)->not->toBeNull()
        ->and($role->organization_id)->toBe($organization->id);
});

it('requires a valid organization token', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->post(route('organization-roles.store'), [
        'name' => 'Orphan',
        'organization' => 'not-a-real-token',
    ])->assertSessionHasErrors('organization');

    expect(OrganizationRole::where('name', 'Orphan')->exists())->toBeFalse();
});

it('requires a name', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('organization-roles.store'), [
        'organization' => $organization->token,
    ])->assertSessionHasErrors('name');
});

it('rejects a duplicate role name within the same organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    OrganizationRole::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Lead',
    ]);

    $this->post(route('organization-roles.store'), [
        'name' => 'Lead',
        'organization' => $organization->token,
    ])->assertSessionHasErrors('name');

    expect(OrganizationRole::where('name', 'Lead')->count())->toBe(1);
});

it('allows the same role name across different organizations', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    OrganizationRole::factory()->create(['organization_id' => $orgA->id, 'name' => 'Member']);

    $this->post(route('organization-roles.store'), [
        'name' => 'Member',
        'organization' => $orgB->token,
    ])->assertRedirect(route('organization-roles.index'));

    expect(OrganizationRole::where('name', 'Member')->count())->toBe(2);
});

it('updates an organization role', function (): void {
    actingAsRole(SystemRole::Developer);
    $role = OrganizationRole::factory()->create(['name' => 'Old']);

    $this->patch(route('organization-roles.update', $role), [
        'name' => 'New',
        'organization' => $role->organization->token,
    ])->assertRedirect();

    expect($role->fresh())->name->toBe('New');
});

it('deletes an unused organization role', function (): void {
    actingAsRole(SystemRole::Developer);
    $role = OrganizationRole::factory()->create();

    $this->delete(route('organization-roles.destroy', $role))->assertRedirect();

    expect(OrganizationRole::withInactive()->find($role->id))->toBeNull();
});

it('refuses to delete a role still assigned to a team', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $role = OrganizationRole::factory()->create(['organization_id' => $organization->id]);
    Team::factory()->create([
        'organization_id' => $organization->id,
        'organization_role_id' => $role->id,
    ]);

    $this->delete(route('organization-roles.destroy', $role))
        ->assertSessionHas('error');

    expect(OrganizationRole::find($role->id))->not->toBeNull();
});

it('never leaks the organization id to the frontend', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $role = OrganizationRole::factory()->create(['organization_id' => $organization->id]);

    $this->get(route('organization-roles.show', $role))
        ->assertInertia(fn ($page) => $page
            ->where('role.organization', $organization->token)
            ->where('role.organization_name', $organization->name)
            ->missing('role.organization_id'));
});

it('filters the index by organization token', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    OrganizationRole::factory()->count(2)->create(['organization_id' => $orgA->id]);
    OrganizationRole::factory()->create(['organization_id' => $orgB->id]);

    $this->get(route('organization-roles.index', ['organization' => $orgA->token]))
        ->assertInertia(fn ($page) => $page
            ->component('OrganizationRoles/Index')
            ->has('roles.data', 2)
            ->where('filters.organization', $orgA->token));
});

it('forbids organization role access without permission', function (): void {
    $this->get(route('organization-roles.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('organization-roles.index'))->assertForbidden();
});
