<?php

use App\Enums\SystemRole;
use App\Models\Milestone;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('reorders milestone columns', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();
    $m1 = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
        'position' => 0,
    ]);
    $m2 = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
        'position' => 1,
    ]);

    $this->patchJson(route('projects.assets.reorder', [$project, $asset]), [
        'milestones' => [$m2->token, $m1->token],
        'tasks' => [],
    ])->assertOk();

    expect($m2->fresh()->position)->toBe(0)
        ->and($m1->fresh()->position)->toBe(1);
});

it('reorders tasks within a column', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();
    $milestone = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $t1 = Task::factory()->create([
        'milestone_id' => $milestone->id,
        'organization_id' => $organization->id,
        'position' => 0,
    ]);
    $t2 = Task::factory()->create([
        'milestone_id' => $milestone->id,
        'organization_id' => $organization->id,
        'position' => 1,
    ]);

    $this->patchJson(route('projects.assets.reorder', [$project, $asset]), [
        'milestones' => [$milestone->token],
        'tasks' => [$milestone->token => [$t2->token, $t1->token]],
    ])->assertOk();

    expect($t2->fresh()->position)->toBe(0)
        ->and($t1->fresh()->position)->toBe(1);
});

it('moves a task to another column', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();
    $m1 = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $m2 = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $task = Task::factory()->create([
        'milestone_id' => $m1->id,
        'organization_id' => $organization->id,
    ]);

    $this->patchJson(route('projects.assets.reorder', [$project, $asset]), [
        'milestones' => [$m1->token, $m2->token],
        'tasks' => [
            $m1->token => [],
            $m2->token => [$task->token],
        ],
    ])->assertOk();

    expect($task->fresh())
        ->milestone_id->toBe($m2->id)
        ->position->toBe(0);
});

it('404s reordering with a milestone from another board', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset] = makeBoard();
    [$otherOrg, $otherProject, $otherAsset] = makeBoard();
    $foreign = Milestone::factory()->create([
        'project_id' => $otherProject->id,
        'asset_id' => $otherAsset->id,
        'organization_id' => $otherOrg->id,
    ]);

    $this->patchJson(route('projects.assets.reorder', [$project, $asset]), [
        'milestones' => [$foreign->token],
        'tasks' => [],
    ])->assertNotFound();

    // The foreign column is untouched.
    expect($foreign->fresh()->project_id)->toBe($otherProject->id);
});

it('forbids reordering without permission', function (): void {
    [, $project, $asset] = makeBoard();
    $noRole = User::factory()->create();

    $this->actingAs($noRole)
        ->patchJson(route('projects.assets.reorder', [$project, $asset]), [
            'milestones' => [],
            'tasks' => [],
        ])->assertForbidden();
});
