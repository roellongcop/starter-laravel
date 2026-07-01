<?php

use App\Enums\SystemRole;
use App\Enums\TaskStatus;
use App\Models\Asset;
use App\Models\DataTag;
use App\Models\Form;
use App\Models\Milestone;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ReferenceFile;
use App\Models\Requirement;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

/**
 * Create a board with a milestone + task, returning everything a requirement
 * test needs.
 *
 * @return array{0: Organization, 1: Project, 2: Asset, 3: Milestone, 4: Task}
 */
function makeTask(): array
{
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

    return [$organization, $project, $asset, $milestone, $task];
}

it('creates a requirement, deriving parent ids from the task and resolving tokens', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset, $milestone, $task] = makeTask();
    $reference = ReferenceFile::factory()->create(['organization_id' => $organization->id]);
    $form = Form::factory()->create(['organization_id' => $organization->id]);
    $tag = DataTag::factory()->create(['organization_id' => $organization->id]);

    $this->post(route('projects.assets.tasks.requirements.store', [$project, $asset, $task]), [
        'name' => 'Signed contract',
        'description' => 'Upload the executed copy.',
        'minimum_files' => 1,
        'maximum_files' => 3,
        'reference_file' => $reference->token,
        'form' => $form->token,
        'tags' => [$tag->token],
    ])->assertRedirect();

    $requirement = Requirement::where('name', 'Signed contract')->first();
    expect($requirement)->not->toBeNull()
        ->and($requirement->task_id)->toBe($task->id)
        ->and($requirement->milestone_id)->toBe($milestone->id)
        ->and($requirement->project_id)->toBe($project->id)
        ->and($requirement->organization_id)->toBe($organization->id)
        ->and($requirement->status)->toBe(TaskStatus::Pending) // always Pending at create
        ->and($requirement->minimum_files)->toBe(1)
        ->and($requirement->maximum_files)->toBe(3)
        ->and($requirement->reference_file_id)->toBe($reference->id)
        ->and($requirement->form_id)->toBe($form->id)
        ->and($requirement->position)->toBe(1)
        ->and($requirement->tags()->count())->toBe(1);
});

it('allows blank file bounds', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset, , $task] = makeTask();

    $this->post(route('projects.assets.tasks.requirements.store', [$project, $asset, $task]), [
        'name' => 'No bounds',
    ])->assertRedirect();

    $requirement = Requirement::where('name', 'No bounds')->first();
    expect($requirement->minimum_files)->toBeNull()
        ->and($requirement->maximum_files)->toBeNull();
});

it('rejects a maximum below the minimum', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset, , $task] = makeTask();

    $this->post(route('projects.assets.tasks.requirements.store', [$project, $asset, $task]), [
        'name' => 'Bad bounds',
        'minimum_files' => 5,
        'maximum_files' => 2,
    ])->assertSessionHasErrors('maximum_files');

    expect(Requirement::where('name', 'Bad bounds')->exists())->toBeFalse();
});

it('ignores a reference file and form from another organization', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset, , $task] = makeTask();
    $foreignRef = ReferenceFile::factory()->create([
        'organization_id' => Organization::factory()->create()->id,
    ]);
    $foreignForm = Form::factory()->create([
        'organization_id' => Organization::factory()->create()->id,
    ]);

    $this->post(route('projects.assets.tasks.requirements.store', [$project, $asset, $task]), [
        'name' => 'Cross-org',
        'reference_file' => $foreignRef->token,
        'form' => $foreignForm->token,
    ])->assertRedirect();

    $requirement = Requirement::where('name', 'Cross-org')->first();
    expect($requirement->reference_file_id)->toBeNull()
        ->and($requirement->form_id)->toBeNull();
});

it('updates a requirement', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset, $milestone, $task] = makeTask();
    $requirement = Requirement::factory()->create([
        'task_id' => $task->id,
        'milestone_id' => $milestone->id,
        'project_id' => $project->id,
        'organization_id' => $organization->id,
        'name' => 'Old',
    ]);

    $this->patch(route('projects.assets.tasks.requirements.update', [$project, $asset, $task, $requirement]), [
        'name' => 'New',
        'minimum_files' => 2,
    ])->assertRedirect();

    expect($requirement->fresh())
        ->name->toBe('New')
        ->minimum_files->toBe(2);
});

it('updates a requirement status inline', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset, $milestone, $task] = makeTask();
    $requirement = Requirement::factory()->create([
        'task_id' => $task->id,
        'milestone_id' => $milestone->id,
        'project_id' => $project->id,
        'organization_id' => $organization->id,
        'status' => TaskStatus::Pending,
    ]);

    $this->patchJson(route('projects.assets.tasks.requirements.status', [$project, $asset, $task, $requirement]), [
        'status' => TaskStatus::Approved->value,
    ])->assertOk()->assertJson(['status' => TaskStatus::Approved->value]);

    expect($requirement->fresh()->status)->toBe(TaskStatus::Approved);
});

it('deletes a requirement', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset, $milestone, $task] = makeTask();
    $requirement = Requirement::factory()->create([
        'task_id' => $task->id,
        'milestone_id' => $milestone->id,
        'project_id' => $project->id,
        'organization_id' => $organization->id,
    ]);

    $this->delete(route('projects.assets.tasks.requirements.destroy', [$project, $asset, $task, $requirement]))
        ->assertRedirect();

    expect(Requirement::withInactive()->find($requirement->id))->toBeNull();
});

it('404s acting on a requirement that belongs to another task', function (): void {
    actingAsRole(SystemRole::Developer);
    [$organization, $project, $asset, $milestone, $task] = makeTask();
    $otherTask = Task::factory()->create([
        'milestone_id' => $milestone->id,
        'organization_id' => $organization->id,
    ]);
    $requirement = Requirement::factory()->create([
        'task_id' => $otherTask->id,
        'milestone_id' => $milestone->id,
        'project_id' => $project->id,
        'organization_id' => $organization->id,
    ]);

    $this->patch(route('projects.assets.tasks.requirements.update', [$project, $asset, $task, $requirement]), [
        'name' => 'Hijack',
    ])->assertNotFound();
});

it('404s creating a requirement on a task from another board', function (): void {
    actingAsRole(SystemRole::Developer);
    [, $project, $asset] = makeBoard();
    [$otherOrg, $otherProject, $otherAsset] = makeBoard();
    $foreignMilestone = Milestone::factory()->create([
        'project_id' => $otherProject->id,
        'asset_id' => $otherAsset->id,
        'organization_id' => $otherOrg->id,
    ]);
    $foreignTask = Task::factory()->create([
        'milestone_id' => $foreignMilestone->id,
        'organization_id' => $otherOrg->id,
    ]);

    $this->post(route('projects.assets.tasks.requirements.store', [$project, $asset, $foreignTask]), [
        'name' => 'Hijack',
    ])->assertNotFound();

    expect(Requirement::where('name', 'Hijack')->exists())->toBeFalse();
});

it('forbids creating a requirement without permission', function (): void {
    [, $project, $asset, , $task] = makeTask();
    $noRole = User::factory()->create();

    $this->actingAs($noRole)
        ->post(route('projects.assets.tasks.requirements.store', [$project, $asset, $task]), [
            'name' => 'Nope',
        ])->assertForbidden();

    expect(Requirement::count())->toBe(0);
});

it('lists the org forms for the form picker', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $form = Form::factory()->create(['organization_id' => $organization->id]);
    // A form in another org must not appear.
    Form::factory()->create(['organization_id' => Organization::factory()->create()->id]);

    $values = collect(
        $this->getJson(route('forms.options', ['organization' => $organization->token]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->json('data')
    )->pluck('value');

    expect($values)->toContain($form->token);
});
