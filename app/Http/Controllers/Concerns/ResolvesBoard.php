<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Asset;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;

/**
 * Scope guards for the project-asset board. Every milestone/task action must
 * belong to the {project}+{asset} pair in the URL, and the asset must be bound to
 * the project — mirroring ProjectAssetController's abort_unless pattern so a user
 * can't reach another project's board through guessed tokens.
 */
trait ResolvesBoard
{
    protected function assertAssetBound(Project $project, Asset $asset): void
    {
        abort_unless(
            $project->assets()->whereKey($asset->getKey())->exists(),
            404,
        );
    }

    protected function assertMilestoneInBoard(Project $project, Asset $asset, Milestone $milestone): void
    {
        abort_unless(
            $milestone->project_id === $project->getKey() && $milestone->asset_id === $asset->getKey(),
            404,
        );
    }

    protected function assertTaskInBoard(Project $project, Asset $asset, Task $task): void
    {
        $task->loadMissing('milestone');

        $this->assertMilestoneInBoard($project, $asset, $task->milestone);
    }
}
