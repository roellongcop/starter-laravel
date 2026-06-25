<?php

use App\Enums\SystemRole;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamCategory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('creates a team category and resolves the organization token to an id', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('team-categories.store'), [
        'name' => 'Engineering',
        'description' => 'Builds things',
        'organization' => $organization->token,
    ])->assertRedirect(route('team-categories.index'));

    $category = TeamCategory::where('name', 'Engineering')->first();
    expect($category)->not->toBeNull()
        ->and($category->description)->toBe('Builds things')
        ->and($category->organization_id)->toBe($organization->id);
});

it('requires a valid organization token', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->post(route('team-categories.store'), [
        'name' => 'Orphan',
        'organization' => 'not-a-real-token',
    ])->assertSessionHasErrors('organization');

    expect(TeamCategory::where('name', 'Orphan')->exists())->toBeFalse();
});

it('requires a name', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('team-categories.store'), [
        'organization' => $organization->token,
    ])->assertSessionHasErrors('name');
});

it('rejects a duplicate category name within the same organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    TeamCategory::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Operations',
    ]);

    $this->post(route('team-categories.store'), [
        'name' => 'Operations',
        'organization' => $organization->token,
    ])->assertSessionHasErrors('name');

    expect(TeamCategory::where('name', 'Operations')->count())->toBe(1);
});

it('allows the same category name across different organizations', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    TeamCategory::factory()->create(['organization_id' => $orgA->id, 'name' => 'Shared']);

    $this->post(route('team-categories.store'), [
        'name' => 'Shared',
        'organization' => $orgB->token,
    ])->assertRedirect(route('team-categories.index'));

    expect(TeamCategory::where('name', 'Shared')->count())->toBe(2);
});

it('updates a team category', function (): void {
    actingAsRole(SystemRole::Developer);
    $category = TeamCategory::factory()->create(['name' => 'Old']);

    $this->patch(route('team-categories.update', $category), [
        'name' => 'New',
        'description' => 'Updated',
        'organization' => $category->organization->token,
    ])->assertRedirect();

    expect($category->fresh())->name->toBe('New')->description->toBe('Updated');
});

it('deletes an unused category', function (): void {
    actingAsRole(SystemRole::Developer);
    $category = TeamCategory::factory()->create();

    $this->delete(route('team-categories.destroy', $category))->assertRedirect();

    expect(TeamCategory::withInactive()->find($category->id))->toBeNull();
});

it('refuses to delete a category still used by a team', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $category = TeamCategory::factory()->create(['organization_id' => $organization->id]);
    Team::factory()->create([
        'organization_id' => $organization->id,
        'team_category_id' => $category->id,
    ]);

    $this->delete(route('team-categories.destroy', $category))
        ->assertSessionHas('error');

    expect(TeamCategory::find($category->id))->not->toBeNull();
});

it('never leaks the organization id to the frontend', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $category = TeamCategory::factory()->create(['organization_id' => $organization->id]);

    $this->get(route('team-categories.show', $category))
        ->assertInertia(fn ($page) => $page
            ->where('category.organization', $organization->token)
            ->where('category.organization_name', $organization->name)
            ->missing('category.organization_id'));
});

it('filters the index by organization token', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    TeamCategory::factory()->count(2)->create(['organization_id' => $orgA->id]);
    TeamCategory::factory()->create(['organization_id' => $orgB->id]);

    $this->get(route('team-categories.index', ['organization' => $orgA->token]))
        ->assertInertia(fn ($page) => $page
            ->component('TeamCategories/Index')
            ->has('categories.data', 2)
            ->where('filters.organization', $orgA->token));
});

it('forbids category access without permission', function (): void {
    $this->get(route('team-categories.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('team-categories.index'))->assertForbidden();
});
