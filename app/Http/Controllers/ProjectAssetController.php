<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncProjectAssetsRequest;
use App\Models\Asset;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class ProjectAssetController extends Controller
{
    /**
     * Replace a project's bound assets with the submitted set. sync() handles
     * both attach and detach, so an empty selection clears them — detaching
     * never deletes the asset itself, only the pivot row.
     */
    public function update(SyncProjectAssetsRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        // Resolve tokens to ids, constrained to the project's own organization so
        // a cross-org token can't be attached (assets are organization-scoped).
        $ids = Asset::query()
            ->where('organization_id', $project->organization_id)
            ->whereIn('token', $request->validated()['assets'])
            ->pluck('id')
            ->all();

        $project->assets()->sync($ids);

        return back()->with('success', 'Project assets updated.');
    }
}
