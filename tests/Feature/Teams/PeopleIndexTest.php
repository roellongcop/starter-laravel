<?php

use App\Enums\SystemRole;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\Person;
use App\Models\Team;
use App\Models\TeamCategory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

/**
 * Create a single Person (team member) in the given (or a new) organization.
 */
function personScaffold(?Organization $organization = null): Person
{
    $organization ??= Organization::factory()->create();
    $category = TeamCategory::factory()->create(['organization_id' => $organization->id]);
    $role = OrganizationRole::factory()->create(['organization_id' => $organization->id]);
    $team = Team::factory()->create([
        'organization_id' => $organization->id,
        'team_category_id' => $category->id,
        'organization_role_id' => $role->id,
    ]);

    return Person::factory()->create([
        'team_id' => $team->id,
        'user_id' => User::factory()->create()->id,
        'organization_role_id' => $role->id,
        'organization_id' => $organization->id,
    ]);
}

it('lists people on the index', function (): void {
    actingAsRole(SystemRole::Developer);
    personScaffold();
    personScaffold();

    $this->get(route('people.index'))
        ->assertInertia(fn ($page) => $page
            ->component('People/Index')
            ->has('people.data', 2));
});

it('shows the member name, team, role and organization without leaking ids', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create(['name' => 'Acme']);
    $category = TeamCategory::factory()->create(['organization_id' => $organization->id]);
    $role = OrganizationRole::factory()->create(['organization_id' => $organization->id, 'name' => 'Lead']);
    $team = Team::factory()->create([
        'organization_id' => $organization->id,
        'team_category_id' => $category->id,
        'organization_role_id' => $role->id,
        'name' => 'Core',
    ]);
    $user = User::factory()->create(['name' => 'Jane Doe']);
    Person::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'organization_role_id' => $role->id,
        'organization_id' => $organization->id,
    ]);

    $this->get(route('people.index'))
        ->assertInertia(fn ($page) => $page
            ->where('people.data.0.name', 'Jane Doe')
            ->where('people.data.0.team', 'Core')
            ->where('people.data.0.role', 'Lead')
            ->where('people.data.0.organization', 'Acme')
            ->missing('people.data.0.organization_id')
            ->missing('people.data.0.user_id'));
});

it('filters people by organization token', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    personScaffold($orgA);
    personScaffold($orgA);
    personScaffold($orgB);

    $this->get(route('people.index', ['organization' => $orgA->token]))
        ->assertInertia(fn ($page) => $page
            ->component('People/Index')
            ->has('people.data', 2)
            ->where('filters.organization', $orgA->token));
});

it('searches people by member name', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $category = TeamCategory::factory()->create(['organization_id' => $organization->id]);
    $role = OrganizationRole::factory()->create(['organization_id' => $organization->id]);
    $team = Team::factory()->create([
        'organization_id' => $organization->id,
        'team_category_id' => $category->id,
        'organization_role_id' => $role->id,
    ]);
    foreach (['Alice Smith', 'Bob Jones'] as $name) {
        Person::factory()->create([
            'team_id' => $team->id,
            'user_id' => User::factory()->create(['name' => $name])->id,
            'organization_role_id' => $role->id,
            'organization_id' => $organization->id,
        ]);
    }

    $this->get(route('people.index', ['search' => 'Alice']))
        ->assertInertia(fn ($page) => $page
            ->has('people.data', 1)
            ->where('people.data.0.name', 'Alice Smith'));
});

it('forbids people access without permission', function (): void {
    $this->get(route('people.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('people.index'))->assertForbidden();
});
