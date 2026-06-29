<?php

use App\Enums\SystemRole;
use App\Models\Asset;
use App\Models\DataTag;
use App\Models\Milestone;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('renders the board for a bound asset', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset] = makeBoard();
    Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $project->organization_id,
        'name' => 'Design',
    ]);

    $this->get(route('projects.assets.show', [$project, $asset]))
        ->assertInertia(fn ($page) => $page
            ->component('Projects/AssetBoard')
            ->where('asset.token', $asset->token)
            ->where('project.token', $project->token)
            ->has('milestones', 1)
            ->where('milestones.0.name', 'Design')
            ->where('canManage', true));
});

it('404s the board when the asset is not bound to the project', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);

    $this->get(route('projects.assets.show', [$project, $asset]))->assertNotFound();
});

it('creates a milestone scoped to the project and asset', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();

    $this->post(route('projects.assets.milestones.store', [$project, $asset]), [
        'name' => 'Discovery',
        'description' => 'Initial research.',
    ])->assertRedirect();

    $milestone = Milestone::where('name', 'Discovery')->first();
    expect($milestone)->not->toBeNull()
        ->and($milestone->project_id)->toBe($project->id)
        ->and($milestone->asset_id)->toBe($asset->id)
        ->and($milestone->organization_id)->toBe($organization->id);
});

it('appends new milestones after existing ones', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset] = makeBoard();
    Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $project->organization_id,
        'position' => 3,
    ]);

    $this->post(route('projects.assets.milestones.store', [$project, $asset]), [
        'name' => 'Next',
    ])->assertRedirect();

    expect(Milestone::where('name', 'Next')->value('position'))->toBe(4);
});

it('requires a milestone name', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset] = makeBoard();

    $this->post(route('projects.assets.milestones.store', [$project, $asset]), [
        'description' => 'No name',
    ])->assertSessionHasErrors('name');
});

it('updates a milestone', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset] = makeBoard();
    $milestone = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $project->organization_id,
        'name' => 'Old',
    ]);

    $this->patch(route('projects.assets.milestones.update', [$project, $asset, $milestone]), [
        'name' => 'New',
        'description' => 'Updated.',
    ])->assertRedirect();

    expect($milestone->fresh())
        ->name->toBe('New')
        ->description->toBe('Updated.');
});

it('deletes a milestone, cascading its tasks and detaching their tags', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();
    $milestone = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $task = Task::factory()->create([
        'milestone_id' => $milestone->id,
        'organization_id' => $organization->id,
    ]);
    $tag = DataTag::factory()->create(['organization_id' => $organization->id]);
    $task->tags()->attach($tag->id);

    $this->delete(route('projects.assets.milestones.destroy', [$project, $asset, $milestone]))
        ->assertRedirect();

    expect(Milestone::withInactive()->find($milestone->id))->toBeNull()
        ->and(Task::withInactive()->find($task->id))->toBeNull()
        ->and(DB::table('taggables')->where('taggable_id', $task->id)->count())->toBe(0);
});

it('404s updating a milestone from another board', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset] = makeBoard();
    [, $otherProject, $otherAsset] = makeBoard();
    $foreign = Milestone::factory()->create([
        'project_id' => $otherProject->id,
        'asset_id' => $otherAsset->id,
        'organization_id' => $otherProject->organization_id,
    ]);

    $this->patch(route('projects.assets.milestones.update', [$project, $asset, $foreign]), [
        'name' => 'Hijack',
    ])->assertNotFound();
});

it('forbids creating a milestone without permission', function (): void {
    [, $project, $asset] = makeBoard();
    $noRole = User::factory()->create();

    $this->actingAs($noRole)
        ->post(route('projects.assets.milestones.store', [$project, $asset]), ['name' => 'Nope'])
        ->assertForbidden();

    expect(Milestone::count())->toBe(0);
});
