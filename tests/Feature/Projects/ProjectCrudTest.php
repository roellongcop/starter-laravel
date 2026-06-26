<?php

use App\Enums\ProjectStatus;
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

it('defaults a new project to pending status', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('projects.store'), [
        'name' => 'No Status',
        'private' => false,
        'organization' => $organization->token,
    ])->assertRedirect(route('projects.index'));

    expect(Project::where('name', 'No Status')->first()->status)
        ->toBe(ProjectStatus::Pending);
});

it('stores and updates the project status', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('projects.store'), [
        'name' => 'With Status',
        'private' => false,
        'status' => ProjectStatus::Approved->value,
        'organization' => $organization->token,
    ])->assertRedirect();

    $project = Project::where('name', 'With Status')->firstOrFail();
    expect($project->status)->toBe(ProjectStatus::Approved);

    $this->patch(route('projects.update', $project), [
        'name' => 'With Status',
        'private' => false,
        'status' => ProjectStatus::Cancelled->value,
        'organization' => $organization->token,
    ])->assertRedirect();

    expect($project->fresh()->status)->toBe(ProjectStatus::Cancelled);
});

it('rejects an invalid project status', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('projects.store'), [
        'name' => 'Bad Status',
        'private' => false,
        'status' => 'Nonsense',
        'organization' => $organization->token,
    ])->assertSessionHasErrors('status');
});

it('updates the project status via the status endpoint', function (): void {
    actingAsRole(SystemRole::Developer);
    $project = Project::factory()->create();

    $this->patch(route('projects.status', $project), [
        'status' => ProjectStatus::Approved->value,
    ])->assertRedirect();

    expect($project->fresh()->status)->toBe(ProjectStatus::Approved);
});

it('returns json for an xhr status update', function (): void {
    actingAsRole(SystemRole::Developer);
    $project = Project::factory()->create();

    $this->patchJson(route('projects.status', $project), [
        'status' => ProjectStatus::Approved->value,
    ])->assertOk()->assertJson(['status' => ProjectStatus::Approved->value]);

    expect($project->fresh()->status)->toBe(ProjectStatus::Approved);
});

it('rejects an invalid status on the status endpoint', function (): void {
    actingAsRole(SystemRole::Developer);
    $project = Project::factory()->create();

    $this->patch(route('projects.status', $project), ['status' => 'Bogus'])
        ->assertSessionHasErrors('status');
});

it('forbids updating project status without update permission', function (): void {
    $project = Project::factory()->create();
    $noRole = User::factory()->create();

    $this->actingAs($noRole)
        ->patch(route('projects.status', $project), [
            'status' => ProjectStatus::Approved->value,
        ])->assertForbidden();
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

it('rejects a duplicate project name within the same organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    Project::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Apollo Platform',
    ]);

    $this->post(route('projects.store'), [
        'name' => 'Apollo Platform',
        'private' => false,
        'organization' => $organization->token,
    ])->assertSessionHasErrors('name');

    expect(Project::where('name', 'Apollo Platform')->count())->toBe(1);
});

it('allows the same project name across different organizations', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    Project::factory()->create([
        'organization_id' => $orgA->id,
        'name' => 'Shared Name',
    ]);

    $this->post(route('projects.store'), [
        'name' => 'Shared Name',
        'private' => false,
        'organization' => $orgB->token,
    ])->assertRedirect(route('projects.index'));

    expect(Project::where('name', 'Shared Name')->count())->toBe(2);
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

it('filters the index by organization token', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    Project::factory()->count(2)->create(['organization_id' => $orgA->id]);
    Project::factory()->create(['organization_id' => $orgB->id]);

    $this->get(route('projects.index', ['organization' => $orgA->token]))
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects.data', 2)
            ->where('filters.organization', $orgA->token));
});

it('forbids project access without permission', function (): void {
    $this->get(route('projects.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('projects.index'))->assertForbidden();
});
