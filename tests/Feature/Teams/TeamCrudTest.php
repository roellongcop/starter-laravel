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
 * Create an organization with a category and a role belonging to it.
 *
 * @return array{0: Organization, 1: TeamCategory, 2: OrganizationRole}
 */
function teamScaffold(): array
{
    $organization = Organization::factory()->create();
    $category = TeamCategory::factory()->create(['organization_id' => $organization->id]);
    $role = OrganizationRole::factory()->create(['organization_id' => $organization->id]);

    return [$organization, $category, $role];
}

it('creates a team, resolves tokens to ids, and syncs members into people', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $category, $role] = teamScaffold();
    $members = User::factory()->count(2)->create();

    $this->post(route('teams.store'), [
        'name' => 'Core Team',
        'description' => 'Primary team',
        'organization' => $organization->token,
        'team_category' => $category->token,
        'organization_role' => $role->token,
        'members' => $members->pluck('token')->all(),
    ])->assertRedirect(route('teams.index'));

    $team = Team::where('name', 'Core Team')->first();
    expect($team)->not->toBeNull()
        ->and($team->organization_id)->toBe($organization->id)
        ->and($team->team_category_id)->toBe($category->id)
        ->and($team->organization_role_id)->toBe($role->id)
        ->and($team->people()->count())->toBe(2);

    // Each person inherits the team's role + organization and joins to the user.
    $person = $team->people()->first();
    expect($person->organization_role_id)->toBe($role->id)
        ->and($person->organization_id)->toBe($organization->id)
        ->and($members->pluck('id'))->toContain($person->user_id);
});

it('requires name, category and role', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('teams.store'), ['organization' => $organization->token])
        ->assertSessionHasErrors(['name', 'team_category', 'organization_role']);
});

it('requires a valid organization token', function (): void {
    actingAsRole(SystemRole::Developer);
    $category = TeamCategory::factory()->create();

    $this->post(route('teams.store'), [
        'name' => 'Orphan',
        'organization' => 'not-a-real-token',
        'team_category' => $category->token,
        'organization_role' => 'whatever',
    ])->assertSessionHasErrors('organization');

    expect(Team::where('name', 'Orphan')->exists())->toBeFalse();
});

it('rejects an organization role that belongs to a different organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $category = TeamCategory::factory()->create(['organization_id' => $orgA->id]);
    $roleB = OrganizationRole::factory()->create(['organization_id' => $orgB->id]);

    $this->post(route('teams.store'), [
        'name' => 'Mismatch',
        'organization' => $orgA->token,
        'team_category' => $category->token,
        'organization_role' => $roleB->token,
    ])->assertSessionHasErrors('organization_role');

    expect(Team::where('name', 'Mismatch')->exists())->toBeFalse();
});

it('rejects a category that belongs to a different organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $categoryB = TeamCategory::factory()->create(['organization_id' => $orgB->id]);
    $roleA = OrganizationRole::factory()->create(['organization_id' => $orgA->id]);

    $this->post(route('teams.store'), [
        'name' => 'Mismatch',
        'organization' => $orgA->token,
        'team_category' => $categoryB->token,
        'organization_role' => $roleA->token,
    ])->assertSessionHasErrors('team_category');

    expect(Team::where('name', 'Mismatch')->exists())->toBeFalse();
});

it('rejects a duplicate team name within the same organization', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $category, $role] = teamScaffold();
    Team::factory()->create([
        'organization_id' => $organization->id,
        'team_category_id' => $category->id,
        'organization_role_id' => $role->id,
        'name' => 'Alpha',
    ]);

    $this->post(route('teams.store'), [
        'name' => 'Alpha',
        'organization' => $organization->token,
        'team_category' => $category->token,
        'organization_role' => $role->token,
    ])->assertSessionHasErrors('name');

    expect(Team::where('name', 'Alpha')->count())->toBe(1);
});

it('allows the same team name across different organizations', function (): void {
    actingAsRole(SystemRole::Developer);
    [$orgA, $catA, $roleA] = teamScaffold();
    $orgB = Organization::factory()->create();
    $catB = TeamCategory::factory()->create(['organization_id' => $orgB->id]);
    $roleB = OrganizationRole::factory()->create(['organization_id' => $orgB->id]);
    Team::factory()->create([
        'organization_id' => $orgA->id,
        'team_category_id' => $catA->id,
        'organization_role_id' => $roleA->id,
        'name' => 'Shared',
    ]);

    $this->post(route('teams.store'), [
        'name' => 'Shared',
        'organization' => $orgB->token,
        'team_category' => $catB->token,
        'organization_role' => $roleB->token,
    ])->assertRedirect(route('teams.index'));

    expect(Team::where('name', 'Shared')->count())->toBe(2);
});

it('lets one user belong to teams in multiple organizations with different roles', function (): void {
    actingAsRole(SystemRole::Developer);
    $user = User::factory()->create();

    $orgA = Organization::factory()->create();
    $catA = TeamCategory::factory()->create(['organization_id' => $orgA->id]);
    $roleA = OrganizationRole::factory()->create(['organization_id' => $orgA->id]);
    $orgB = Organization::factory()->create();
    $catB = TeamCategory::factory()->create(['organization_id' => $orgB->id]);
    $roleB = OrganizationRole::factory()->create(['organization_id' => $orgB->id]);

    $this->post(route('teams.store'), [
        'name' => 'Team A',
        'organization' => $orgA->token,
        'team_category' => $catA->token,
        'organization_role' => $roleA->token,
        'members' => [$user->token],
    ])->assertRedirect();

    $this->post(route('teams.store'), [
        'name' => 'Team B',
        'organization' => $orgB->token,
        'team_category' => $catB->token,
        'organization_role' => $roleB->token,
        'members' => [$user->token],
    ])->assertRedirect();

    $people = Person::where('user_id', $user->id)->get();
    expect($people)->toHaveCount(2)
        ->and($people->pluck('organization_role_id')->sort()->values()->all())
        ->toBe(collect([$roleA->id, $roleB->id])->sort()->values()->all());
});

it('removes members that are no longer selected on update', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $category, $role] = teamScaffold();
    $keep = User::factory()->create();
    $drop = User::factory()->create();
    $team = Team::factory()->create([
        'organization_id' => $organization->id,
        'team_category_id' => $category->id,
        'organization_role_id' => $role->id,
    ]);
    foreach ([$keep, $drop] as $user) {
        Person::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'organization_role_id' => $role->id,
            'organization_id' => $organization->id,
        ]);
    }

    $this->patch(route('teams.update', $team), [
        'name' => $team->name,
        'organization' => $organization->token,
        'team_category' => $category->token,
        'organization_role' => $role->token,
        'members' => [$keep->token],
    ])->assertRedirect();

    expect($team->people()->count())->toBe(1)
        ->and($team->people()->first()->user_id)->toBe($keep->id);
});

it('updates existing members when the team role changes', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $category = TeamCategory::factory()->create(['organization_id' => $organization->id]);
    $roleOld = OrganizationRole::factory()->create(['organization_id' => $organization->id]);
    $roleNew = OrganizationRole::factory()->create(['organization_id' => $organization->id]);
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'organization_id' => $organization->id,
        'team_category_id' => $category->id,
        'organization_role_id' => $roleOld->id,
    ]);
    Person::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'organization_role_id' => $roleOld->id,
        'organization_id' => $organization->id,
    ]);

    $this->patch(route('teams.update', $team), [
        'name' => $team->name,
        'organization' => $organization->token,
        'team_category' => $category->token,
        'organization_role' => $roleNew->token,
        'members' => [$user->token],
    ])->assertRedirect();

    expect(Person::where('team_id', $team->id)->where('user_id', $user->id)->first()->organization_role_id)
        ->toBe($roleNew->id);
});

it('deletes a team and cascades its members', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $category, $role] = teamScaffold();
    $team = Team::factory()->create([
        'organization_id' => $organization->id,
        'team_category_id' => $category->id,
        'organization_role_id' => $role->id,
    ]);
    $person = Person::factory()->create([
        'team_id' => $team->id,
        'user_id' => User::factory()->create()->id,
        'organization_role_id' => $role->id,
        'organization_id' => $organization->id,
    ]);

    $this->delete(route('teams.destroy', $team))->assertRedirect();

    expect(Team::withInactive()->find($team->id))->toBeNull()
        ->and(Person::withInactive()->find($person->id))->toBeNull();
});

it('never leaks ids and renders the roster on show', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $category, $role] = teamScaffold();
    $user = User::factory()->create(['name' => 'Jane Doe']);
    $team = Team::factory()->create([
        'organization_id' => $organization->id,
        'team_category_id' => $category->id,
        'organization_role_id' => $role->id,
    ]);
    Person::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'organization_role_id' => $role->id,
        'organization_id' => $organization->id,
    ]);

    $this->get(route('teams.show', $team))
        ->assertInertia(fn ($page) => $page
            ->component('Teams/Show')
            ->where('team.organization', $organization->token)
            ->where('team.organization_role', $role->token)
            ->missing('team.organization_id')
            ->missing('team.organization_role_id')
            ->has('team.roster', 1)
            ->where('team.roster.0.name', 'Jane Doe')
            ->where('team.roster.0.role', $role->name));
});

it('filters the index by organization token', function (): void {
    actingAsRole(SystemRole::Developer);
    [$orgA, $catA, $roleA] = teamScaffold();
    $orgB = Organization::factory()->create();
    $catB = TeamCategory::factory()->create(['organization_id' => $orgB->id]);
    $roleB = OrganizationRole::factory()->create(['organization_id' => $orgB->id]);
    Team::factory()->count(2)->create([
        'organization_id' => $orgA->id,
        'team_category_id' => $catA->id,
        'organization_role_id' => $roleA->id,
    ]);
    Team::factory()->create([
        'organization_id' => $orgB->id,
        'team_category_id' => $catB->id,
        'organization_role_id' => $roleB->id,
    ]);

    $this->get(route('teams.index', ['organization' => $orgA->token]))
        ->assertInertia(fn ($page) => $page
            ->component('Teams/Index')
            ->has('teams.data', 2)
            ->where('filters.organization', $orgA->token));
});

it('forbids team access without permission', function (): void {
    $this->get(route('teams.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('teams.index'))->assertForbidden();
});
