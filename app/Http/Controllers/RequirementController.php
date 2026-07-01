<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Controllers\Concerns\ResolvesBoard;
use App\Http\Requests\StoreRequirementRequest;
use App\Http\Requests\UpdateRequirementRequest;
use App\Http\Requests\UpdateRequirementStatusRequest;
use App\Models\Asset;
use App\Models\Form;
use App\Models\Project;
use App\Models\ReferenceFile;
use App\Models\Requirement;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Requirements are deliverables nested under a board task. Every action is
 * scoped to the {project}+{asset}+{task} in the URL (via ResolvesBoard), and a
 * requirement's project_id/milestone_id/organization_id are derived from the
 * task — never accepted from the client. Managed inline from the task detail
 * panel; there is no standalone index page.
 */
class RequirementController extends Controller
{
    use ResolvesBoard;

    public function store(StoreRequirementRequest $request, Project $project, Asset $asset, Task $task): RedirectResponse
    {
        $this->authorize('create', Requirement::class);
        $this->assertAssetBound($project, $asset);
        $this->assertTaskInBoard($project, $asset, $task);

        $validated = $request->validated();

        $data = $this->resolveData($task, $validated);
        // New requirements always start Pending; status changes happen inline.
        $data['status'] = TaskStatus::Pending->value;
        $data['position'] = (int) Requirement::query()
            ->where('task_id', $task->getKey())
            ->max('position') + 1;

        $requirement = Requirement::create($data);
        $requirement->syncDataTags($validated['tags'] ?? []);

        return back()->with('success', 'Requirement created.');
    }

    public function update(UpdateRequirementRequest $request, Project $project, Asset $asset, Task $task, Requirement $requirement): RedirectResponse
    {
        $this->authorize('update', $requirement);
        $this->assertAssetBound($project, $asset);
        $this->assertTaskInBoard($project, $asset, $task);
        $this->assertRequirementOnTask($task, $requirement);

        $validated = $request->validated();

        $requirement->update($this->resolveData($task, $validated));
        $requirement->syncDataTags($validated['tags'] ?? []);

        return back()->with('success', 'Requirement updated.');
    }

    public function destroy(Project $project, Asset $asset, Task $task, Requirement $requirement): RedirectResponse
    {
        $this->authorize('delete', $requirement);
        $this->assertAssetBound($project, $asset);
        $this->assertTaskInBoard($project, $asset, $task);
        $this->assertRequirementOnTask($task, $requirement);

        $requirement->delete();

        return back()->with('success', 'Requirement deleted.');
    }

    /**
     * Inline status change for a requirement (posted via axios from the detail
     * panel), kept separate from the full edit form — mirrors a task's status.
     */
    public function updateStatus(UpdateRequirementStatusRequest $request, Project $project, Asset $asset, Task $task, Requirement $requirement): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $requirement);
        $this->assertAssetBound($project, $asset);
        $this->assertTaskInBoard($project, $asset, $task);
        $this->assertRequirementOnTask($task, $requirement);

        $requirement->update(['status' => $request->validated()['status']]);

        if ($request->expectsJson()) {
            return response()->json(['status' => $requirement->status->value]);
        }

        return back()->with('success', 'Requirement status updated.');
    }

    protected function assertRequirementOnTask(Task $task, Requirement $requirement): void
    {
        abort_unless($requirement->task_id === $task->getKey(), 404);
    }

    /**
     * Build the persistable attributes: the parent FKs are denormalized from the
     * task (task → milestone → project), and the reference-file / form tokens are
     * resolved and re-scoped to the task's organization. (status/position are set
     * by the caller on create.)
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function resolveData(Task $task, array $validated): array
    {
        $task->loadMissing('milestone');
        $organizationId = $task->organization_id;

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'organization_id' => $organizationId,
            'project_id' => $task->milestone->project_id,
            'milestone_id' => $task->milestone_id,
            'task_id' => $task->getKey(),
            'minimum_files' => $validated['minimum_files'] ?? null,
            'maximum_files' => $validated['maximum_files'] ?? null,
            'reference_file_id' => $this->referenceFileId($organizationId, $validated['reference_file'] ?? null),
            'form_id' => $this->formId($organizationId, $validated['form'] ?? null),
        ];
    }

    protected function referenceFileId(int $organizationId, ?string $token): ?int
    {
        if (! $token) {
            return null;
        }

        return ReferenceFile::query()
            ->where('organization_id', $organizationId)
            ->where('token', $token)
            ->value('id');
    }

    protected function formId(int $organizationId, ?string $token): ?int
    {
        if (! $token) {
            return null;
        }

        return Form::query()
            ->where('organization_id', $organizationId)
            ->where('token', $token)
            ->value('id');
    }
}
