<?php

use App\Enums\SystemRole;
use App\Enums\TaskStatus;
use App\Models\DataTag;
use App\Models\Milestone;
use App\Models\Organization;
use App\Models\Person;
use App\Models\ReferenceFile;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('creates a task, assigning an org team or person and resolving reference / tag tokens', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();
    $milestone = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $team = Team::factory()->create(['organization_id' => $organization->id]);
    $person = Person::factory()->create(['organization_id' => $organization->id]);
    $reference = ReferenceFile::factory()->create(['organization_id' => $organization->id]);
    $tag = DataTag::factory()->create(['organization_id' => $organization->id]);

    $this->post(route('projects.assets.tasks.store', [$project, $asset]), [
        'name' => 'Draft proposal',
        'description' => 'First cut.',
        'milestone' => $milestone->token,
        'status' => TaskStatus::Submitted->value,
        'assigned_to' => $team->token,
        'approver' => $person->token,
        'reference_file' => $reference->token,
        'tags' => [$tag->token],
    ])->assertRedirect();

    $task = Task::where('name', 'Draft proposal')->first();
    expect($task)->not->toBeNull()
        ->and($task->milestone_id)->toBe($milestone->id)
        ->and($task->organization_id)->toBe($organization->id)
        ->and($task->status)->toBe(TaskStatus::Submitted)
        ->and($task->assignee_type)->toBe('team')
        ->and($task->assignee_id)->toBe($team->id)
        ->and($task->approver_type)->toBe('person')
        ->and($task->approver_id)->toBe($person->id)
        ->and($task->observer_id)->toBeNull()
        ->and($task->reference_file_id)->toBe($reference->id)
        ->and($task->position)->toBe(1)
        ->and($task->tags()->count())->toBe(1);
});

it('ignores an assignee from another organization', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();
    $milestone = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $foreignTeam = Team::factory()->create([
        'organization_id' => Organization::factory()->create()->id,
    ]);

    $this->post(route('projects.assets.tasks.store', [$project, $asset]), [
        'name' => 'Foreign assignee',
        'milestone' => $milestone->token,
        'assigned_to' => $foreignTeam->token,
    ])->assertRedirect();

    $task = Task::where('name', 'Foreign assignee')->first();
    expect($task->assignee_id)->toBeNull()
        ->and($task->assignee_type)->toBeNull();
});

it('lists the org teams and people for the assignee picker', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $team = Team::factory()->create(['organization_id' => $organization->id]);
    $person = Person::factory()->create(['organization_id' => $organization->id]);
    // A team in another org must not appear.
    Team::factory()->create(['organization_id' => Organization::factory()->create()->id]);

    $tokens = collect(
        $this->getJson(route('task-assignees.options', ['organization' => $organization->token]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data')
    )->pluck('value');

    expect($tokens)->toContain($team->token, $person->token);
});

it('returns no assignee options without an organization', function (): void {
    actingAsRole(SystemRole::Developer);
    Team::factory()->create();

    $this->getJson(route('task-assignees.options'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('persists the private flag and due date', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();
    $milestone = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);

    $this->post(route('projects.assets.tasks.store', [$project, $asset]), [
        'name' => 'Private task',
        'milestone' => $milestone->token,
        'private' => true,
        'due_date' => '2026-07-15',
    ])->assertRedirect();

    $task = Task::where('name', 'Private task')->first();
    expect($task->private)->toBeTrue()
        ->and($task->status)->toBe(TaskStatus::Pending) // defaults when omitted
        ->and($task->due_date->toDateString())->toBe('2026-07-15');
});

it('ignores a reference file from another organization', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();
    $milestone = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $foreignRef = ReferenceFile::factory()->create([
        'organization_id' => Organization::factory()->create()->id,
    ]);

    $this->post(route('projects.assets.tasks.store', [$project, $asset]), [
        'name' => 'No ref',
        'milestone' => $milestone->token,
        'reference_file' => $foreignRef->token,
    ])->assertRedirect();

    expect(Task::where('name', 'No ref')->value('reference_file_id'))->toBeNull();
});

it('updates a task and moves it to another milestone', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();
    $from = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $to = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $task = Task::factory()->create([
        'milestone_id' => $from->id,
        'organization_id' => $organization->id,
        'name' => 'Old',
    ]);

    $this->patch(route('projects.assets.tasks.update', [$project, $asset, $task]), [
        'name' => 'New',
        'milestone' => $to->token,
    ])->assertRedirect();

    expect($task->fresh())
        ->name->toBe('New')
        ->milestone_id->toBe($to->id);
});

it('deletes a task', function (): void {
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

    $this->delete(route('projects.assets.tasks.destroy', [$project, $asset, $task]))
        ->assertRedirect();

    expect(Task::withInactive()->find($task->id))->toBeNull();
});

it('404s creating a task in a milestone from another board', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset] = makeBoard();
    [$otherOrg, $otherProject, $otherAsset] = makeBoard();
    $foreign = Milestone::factory()->create([
        'project_id' => $otherProject->id,
        'asset_id' => $otherAsset->id,
        'organization_id' => $otherOrg->id,
    ]);

    $this->post(route('projects.assets.tasks.store', [$project, $asset]), [
        'name' => 'Hijack',
        'milestone' => $foreign->token,
    ])->assertNotFound();

    expect(Task::where('name', 'Hijack')->exists())->toBeFalse();
});

it('404s updating a task from another board', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset] = makeBoard();
    [$otherOrg, $otherProject, $otherAsset] = makeBoard();
    $here = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $foreignMilestone = Milestone::factory()->create([
        'project_id' => $otherProject->id,
        'asset_id' => $otherAsset->id,
        'organization_id' => $otherOrg->id,
    ]);
    $foreignTask = Task::factory()->create([
        'milestone_id' => $foreignMilestone->id,
        'organization_id' => $otherOrg->id,
    ]);

    $this->patch(route('projects.assets.tasks.update', [$project, $asset, $foreignTask]), [
        'name' => 'Hijack',
        'milestone' => $here->token,
    ])->assertNotFound();
});

it('forbids creating a task without permission', function (): void {
    [$organization, $project, $asset] = makeBoard();
    $milestone = Milestone::factory()->create([
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'organization_id' => $organization->id,
    ]);
    $noRole = User::factory()->create();

    $this->actingAs($noRole)
        ->post(route('projects.assets.tasks.store', [$project, $asset]), [
            'name' => 'Nope',
            'milestone' => $milestone->token,
        ])->assertForbidden();

    expect(Task::count())->toBe(0);
});
