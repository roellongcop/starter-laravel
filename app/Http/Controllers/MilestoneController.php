<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesBoard;
use App\Http\Requests\StoreMilestoneRequest;
use App\Http\Requests\UpdateMilestoneRequest;
use App\Models\Asset;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class MilestoneController extends Controller
{
    use ResolvesBoard;

    public function store(StoreMilestoneRequest $request, Project $project, Asset $asset): RedirectResponse
    {
        $this->authorize('create', Milestone::class);
        $this->assertAssetBound($project, $asset);

        $data = $request->validated();
        $data['project_id'] = $project->getKey();
        $data['asset_id'] = $asset->getKey();
        $data['organization_id'] = $project->organization_id;
        $data['position'] = (int) Milestone::query()
            ->where('project_id', $project->getKey())
            ->where('asset_id', $asset->getKey())
            ->max('position') + 1;

        Milestone::create($data);

        return back()->with('success', 'Milestone created.');
    }

    public function update(UpdateMilestoneRequest $request, Project $project, Asset $asset, Milestone $milestone): RedirectResponse
    {
        $this->authorize('update', $milestone);
        $this->assertAssetBound($project, $asset);
        $this->assertMilestoneInBoard($project, $asset, $milestone);

        $milestone->update($request->validated());

        return back()->with('success', 'Milestone updated.');
    }

    public function destroy(Project $project, Asset $asset, Milestone $milestone): RedirectResponse
    {
        $this->authorize('delete', $milestone);
        $this->assertAssetBound($project, $asset);
        $this->assertMilestoneInBoard($project, $asset, $milestone);

        // Delete child tasks through Eloquent so HasDataTags detaches their tag
        // pivots — a DB FK cascade would skip model events and orphan taggables.
        $milestone->tasks()->cursor()->each(fn (Task $task) => $task->delete());
        $milestone->delete();

        return back()->with('success', 'Milestone deleted.');
    }
}
