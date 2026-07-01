<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Controllers\Concerns\ResolvesBoard;
use App\Http\Controllers\Concerns\SerializesBoard;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Asset;
use App\Models\Milestone;
use App\Models\Person;
use App\Models\Project;
use App\Models\ReferenceFile;
use App\Models\Requirement;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    use ResolvesBoard;
    use SerializesBoard;

    /**
     * The dedicated task detail page: the task's details plus its full list of
     * requirements. Reached by clicking a task card on the board. Scoped to the
     * {project}+{asset} pair, and the task must belong to that board.
     */
    public function show(Project $project, Asset $asset, Task $task): Response
    {
        $this->authorize('view', $project);
        $this->assertAssetBound($project, $asset);
        $this->assertTaskInBoard($project, $asset, $task);

        // A Person's display name lives on its user, so eager-load that nested
        // relation for the polymorphic assignee/approver/observer morphs.
        $loadAssignee = function (Relation $relation): void {
            if ($relation instanceof MorphTo) {
                $relation->morphWith([Person::class => ['user']]);
            }
        };

        $task->load([
            'milestone',
            'assignee' => $loadAssignee,
            'approver' => $loadAssignee,
            'observer' => $loadAssignee,
            'referenceFile',
            'tags',
            'requirements.referenceFile',
            'requirements.form',
            'requirements.tags',
        ])->loadCount('requirements');

        $asset->loadMissing('organization');

        return Inertia::render('Projects/TaskShow', [
            'project' => ['token' => $project->token, 'name' => $project->name],
            'asset' => [
                'token' => $asset->token,
                'name' => $asset->name,
                'organization' => $asset->organization->token,
            ],
            'milestone' => ['token' => $task->milestone->token, 'name' => $task->milestone->name],
            'task' => [
                ...$this->taskRow($task, $task->milestone->token),
                'requirements' => $task->requirements
                    ->map(fn (Requirement $requirement): array => $this->requirementRow($requirement))
                    ->all(),
            ],
            'taskStatusOptions' => TaskStatus::options(),
        ]);
    }

    public function store(StoreTaskRequest $request, Project $project, Asset $asset): RedirectResponse
    {
        $this->authorize('create', Task::class);
        $this->assertAssetBound($project, $asset);

        $validated = $request->validated();
        $milestone = $this->resolveMilestone($project, $asset, $validated['milestone']);

        $data = $this->resolveTaskData($project, $validated);
        $data['milestone_id'] = $milestone->getKey();
        $data['organization_id'] = $project->organization_id;
        // New tasks always start Pending; status changes happen inline on the card.
        $data['status'] = TaskStatus::Pending->value;
        $data['position'] = (int) Task::query()
            ->where('milestone_id', $milestone->getKey())
            ->max('position') + 1;

        $task = Task::create($data);
        $task->syncDataTags($validated['tags'] ?? []);

        return back()->with('success', 'Task created.');
    }

    public function update(UpdateTaskRequest $request, Project $project, Asset $asset, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $this->assertAssetBound($project, $asset);
        $this->assertTaskInBoard($project, $asset, $task);

        $validated = $request->validated();
        $milestone = $this->resolveMilestone($project, $asset, $validated['milestone']);

        $data = $this->resolveTaskData($project, $validated);
        $data['milestone_id'] = $milestone->getKey();

        $task->update($data);
        $task->syncDataTags($validated['tags'] ?? []);

        return back()->with('success', 'Task updated.');
    }

    public function destroy(Project $project, Asset $asset, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $this->assertAssetBound($project, $asset);
        $this->assertTaskInBoard($project, $asset, $task);

        $task->delete();

        return back()->with('success', 'Task deleted.');
    }

    /**
     * Inline status change for a task card (posted via axios from the board),
     * kept separate from the full edit form.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Project $project, Asset $asset, Task $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);
        $this->assertAssetBound($project, $asset);
        $this->assertTaskInBoard($project, $asset, $task);

        $task->update(['status' => $request->validated()['status']]);

        if ($request->expectsJson()) {
            return response()->json(['status' => $task->status->value]);
        }

        return back()->with('success', 'Task status updated.');
    }

    /**
     * Resolve a milestone token to a Milestone on this board (404 otherwise).
     */
    protected function resolveMilestone(Project $project, Asset $asset, string $token): Milestone
    {
        $milestone = Milestone::query()
            ->where('project_id', $project->getKey())
            ->where('asset_id', $asset->getKey())
            ->where('token', $token)
            ->first();

        abort_unless($milestone !== null, 404);

        return $milestone;
    }

    /**
     * Translate the wire-only tokens (assignee/approver/observer, reference file)
     * into persistable attributes, all re-scoped to the project's organization —
     * each assignee is a Team or Person within the org. (milestone_id/
     * organization_id/position are set by the caller.)
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function resolveTaskData(Project $project, array $validated): array
    {
        $org = $project->organization_id;
        $assignee = $this->resolveAssignee($validated['assigned_to'] ?? null, $org);
        $approver = $this->resolveAssignee($validated['approver'] ?? null, $org);
        $observer = $this->resolveAssignee($validated['observer'] ?? null, $org);

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'private' => $validated['private'] ?? false,
            'due_date' => $validated['due_date'] ?? null,
            'assignee_type' => $assignee['type'],
            'assignee_id' => $assignee['id'],
            'approver_type' => $approver['type'],
            'approver_id' => $approver['id'],
            'observer_type' => $observer['type'],
            'observer_id' => $observer['id'],
            'reference_file_id' => $this->referenceFileId($project, $validated['reference_file'] ?? null),
        ];
    }

    /**
     * Resolve a Team-or-Person token to its morph (type, id) within the org. An
     * absent or foreign/unknown token resolves to no assignment.
     *
     * @return array{type: string|null, id: int|null}
     */
    protected function resolveAssignee(?string $token, int $organizationId): array
    {
        if (! $token) {
            return ['type' => null, 'id' => null];
        }

        $team = Team::query()->where('organization_id', $organizationId)->where('token', $token)->first();
        if ($team !== null) {
            return ['type' => $team->getMorphClass(), 'id' => $team->getKey()];
        }

        $person = Person::query()->where('organization_id', $organizationId)->where('token', $token)->first();
        if ($person !== null) {
            return ['type' => $person->getMorphClass(), 'id' => $person->getKey()];
        }

        return ['type' => null, 'id' => null];
    }

    protected function referenceFileId(Project $project, ?string $token): ?int
    {
        if (! $token) {
            return null;
        }

        return ReferenceFile::query()
            ->where('organization_id', $project->organization_id)
            ->where('token', $token)
            ->value('id');
    }
}
