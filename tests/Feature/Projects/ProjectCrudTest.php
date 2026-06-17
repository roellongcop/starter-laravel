<?php

use App\Enums\SystemRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('creates a project and resolves the organization token to an id', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('projects.store'), [
        'name' => 'Website Redesign',
        'description' => 'A demo project',
        'private' => true,
        'organization' => $organization->token,
    ])->assertRedirect(route('projects.index'));

    $project = Project::where('name', 'Website Redesign')->first();
    expect($project)->not->toBeNull()
        ->and($project->private)->toBeTrue()
        ->and($project->organization_id)->toBe($organization->id);
});

it('requires a valid organization token', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->post(route('projects.store'), [
        'name' => 'Orphan',
        'private' => false,
        'organization' => 'not-a-real-token',
    ])->assertSessionHasErrors('organization');

    expect(Project::where('name', 'Orphan')->exists())->toBeFalse();
});

it('updates a project', function (): void {
    actingAsRole(SystemRole::Developer);
    $project = Project::factory()->create(['name' => 'Old', 'private' => false]);

    $this->patch(route('projects.update', $project), [
        'name' => 'Updated',
        'private' => true,
        'organization' => $project->organization->token,
    ])->assertRedirect();

    expect($project->fresh())
        ->name->toBe('Updated')
        ->private->toBeTrue();
});

it('deletes a project', function (): void {
    actingAsRole(SystemRole::Developer);
    $project = Project::factory()->create();

    $this->delete(route('projects.destroy', $project))->assertRedirect();
    expect(Project::withInactive()->find($project->id))->toBeNull();
});

it('bulk inactivates and deletes projects', function (): void {
    actingAsRole(SystemRole::Developer);
    $projects = Project::factory()->count(2)->create();
    $tokens = $projects->pluck('token')->all();

    $this->post(route('projects.bulk'), [
        'process' => 'in_active',
        'tokens' => $tokens,
    ])->assertRedirect();
    expect(Project::query()->count())->toBe(0)
        ->and(Project::onlyInactive()->count())->toBe(2);

    $this->post(route('projects.bulk'), [
        'process' => 'delete',
        'tokens' => $tokens,
    ])->assertRedirect();
    expect(Project::withInactive()->count())->toBe(0);
});

it('renders projects on the index page (scroll prop loads on first paint)', function (): void {
    actingAsRole(SystemRole::Developer);
    Project::factory()->count(3)->create();

    $this->get(route('projects.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects.data', 3));
});

it('never leaks the organization id to the frontend', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $this->get(route('projects.show', $project))
        ->assertInertia(fn ($page) => $page
            ->where('project.organization', $organization->token)
            ->where('project.organization_name', $organization->name)
            ->missing('project.organization_id'));
});

it('forbids project access without permission', function (): void {
    $this->get(route('projects.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('projects.index'))->assertForbidden();
});
