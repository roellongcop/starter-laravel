<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Concerns\ResolvesBoard;
use App\Http\Controllers\Concerns\SerializesAssets;
use App\Http\Controllers\Concerns\SerializesBoard;
use App\Models\Asset;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The project-scoped asset detail page: a Kanban board of milestones (columns) and
 * tasks (cards) for one project's view of a bound asset. Milestone/task CRUD lives
 * in MilestoneController/TaskController; this controller renders the board and
 * persists drag reorders.
 */
class ProjectAssetBoardController extends Controller
{
    use ResolvesBoard;
    use SerializesAssets;
    use SerializesBoard;

    public function show(Request $request, Project $project, Asset $asset): Response
    {
        $this->authorize('view', $project);

        // Fetch the bound asset with its relations + pivot (per-project workflow
        // status) for the details panel; 404 if it isn't bound to this project.
        $binding = $project->assets()
            ->with(['organization', 'tags'])
            ->whereKey($asset->getKey())
            ->first();
        abort_if($binding === null, 404);

        $milestones = Milestone::query()
            ->where('project_id', $project->getKey())
            ->where('asset_id', $asset->getKey())
            ->with([
                'tasks.assignee',
                'tasks.approver',
                'tasks.observer',
                'tasks.referenceFile',
                'tasks.tags',
            ])
            ->orderBy('position')
            ->get();

        $user = $request->user();
        $canManage = $user !== null && (
            $user->can('create', Milestone::class)
            || $user->can('update', Milestone::class)
            || $user->can('create', Task::class)
            || $user->can('update', Task::class)
        );

        return Inertia::render('Projects/AssetBoard', [
            'project' => ['token' => $project->token, 'name' => $project->name],
            'asset' => [
                ...$this->assetRow($binding),
                'status' => $binding->pivot->getAttribute('status'),
            ],
            'milestones' => $milestones->map(fn (Milestone $milestone): array => $this->milestoneRow($milestone))->all(),
            'canManage' => $canManage,
            // Workflow statuses for the inline per-project-asset status dropdown.
            'statusOptions' => ProjectStatus::options(),
        ]);
    }

    /**
     * Persist a drag-and-drop rearrangement of the whole board in one shot — column
     * order, card order within a column, and cross-column moves. The frontend posts
     * the full ordering on drag end, which avoids partial-state drift.
     */
    public function reorder(Request $request, Project $project, Asset $asset): JsonResponse|RedirectResponse
    {
        $this->authorize('update', Milestone::class);
        $this->authorize('update', Task::class);
        $this->assertAssetBound($project, $asset);

        $validated = $request->validate([
            'milestones' => ['array'],
            'milestones.*' => ['string', 'exists:milestones,token'],
            'tasks' => ['array'],
            'tasks.*' => ['array'],
            'tasks.*.*' => ['string', 'exists:tasks,token'],
        ]);

        DB::transaction(function () use ($project, $asset, $validated): void {
            $boardMilestones = Milestone::query()
                ->where('project_id', $project->getKey())
                ->where('asset_id', $asset->getKey())
                ->get()
                ->keyBy('token');

            $boardMilestoneIds = $boardMilestones->pluck('id')->all();

            // 1) Column order.
            foreach (array_values($validated['milestones'] ?? []) as $index => $token) {
                $milestone = $boardMilestones->get($token);
                abort_unless($milestone !== null, 404);
                $milestone->update(['position' => $index]);
            }

            // 2) Card order within each column; a task listed under a different
            //    column token is moved there (milestone_id reassigned). Tasks are
            //    constrained to this board so a foreign task can't be pulled in.
            foreach ($validated['tasks'] ?? [] as $milestoneToken => $taskTokens) {
                $milestone = $boardMilestones->get($milestoneToken);
                abort_unless($milestone !== null, 404);

                $tasks = Task::query()
                    ->whereIn('milestone_id', $boardMilestoneIds)
                    ->whereIn('token', $taskTokens)
                    ->get()
                    ->keyBy('token');

                foreach (array_values($taskTokens) as $index => $taskToken) {
                    $task = $tasks->get($taskToken);
                    abort_unless($task !== null, 404);
                    $task->update(['milestone_id' => $milestone->getKey(), 'position' => $index]);
                }
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
