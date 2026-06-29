<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesBoard;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Asset;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ReferenceFile;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    use ResolvesBoard;

    public function store(StoreTaskRequest $request, Project $project, Asset $asset): RedirectResponse
    {
        $this->authorize('create', Task::class);
        $this->assertAssetBound($project, $asset);

        $validated = $request->validated();
        $milestone = $this->resolveMilestone($project, $asset, $validated['milestone']);

        $data = $this->resolveTaskData($project, $validated);
        $data['milestone_id'] = $milestone->getKey();
        $data['organization_id'] = $project->organization_id;
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
     * Translate the wire-only tokens (users, reference file) into ids — the
     * reference file is re-scoped to the project's organization — and assemble the
     * persistable attributes (milestone_id/organization_id/position set by caller).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function resolveTaskData(Project $project, array $validated): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'private' => $validated['private'] ?? false,
            'due_date' => $validated['due_date'] ?? null,
            'assigned_to_id' => $this->userId($validated['assigned_to'] ?? null),
            'approver_id' => $this->userId($validated['approver'] ?? null),
            'observer_id' => $this->userId($validated['observer'] ?? null),
            'reference_file_id' => $this->referenceFileId($project, $validated['reference_file'] ?? null),
        ];
    }

    protected function userId(?string $token): ?int
    {
        return $token ? User::where('token', $token)->value('id') : null;
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
