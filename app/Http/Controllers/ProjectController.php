<?php

namespace App\Http\Controllers;

use App\Filters\ProjectAssetFilters;
use App\Filters\ProjectFilters;
use App\Http\Controllers\Concerns\SerializesAssets;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use SerializesAssets;

    public function index(Request $request, ProjectFilters $filters): Response
    {
        $this->authorize('viewAny', Project::class);

        $projects = $filters->apply(Project::query()->with(['organization', 'tags']))
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (Project $project) => $this->row($project));

        return Inertia::render('Projects/Index', [
            // Inertia::scroll() merges (appends) the paginator's `data` wrapper on
            // partial reloads, driving the <InfiniteScroll> card grid; cursor
            // metadata is derived from the CursorPaginator automatically.
            'projects' => Inertia::scroll($projects),
            'filters' => $filters->echoBack(),
            'organizations' => $this->organizationOptions(),
            'dataTags' => $this->dataTagOptions(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $data = $this->resolveOrganization($request->validated());
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $project = Project::create($data);
        $project->syncDataTags($tags);

        return redirect()->route('projects.index')->with('success', 'Project created.');
    }

    public function show(Request $request, ProjectAssetFilters $filters, Project $project): Response
    {
        $this->authorize('view', $project);

        $project->loadMissing(['organization', 'tags']);

        // The project's bound assets — keyset cursor-paginated and server-side
        // searchable (the set can be large), serialized identically to the Assets
        // module (by reference; a renamed asset reflects here on next load).
        $assets = $filters->apply(
            Asset::query()
                ->whereHas('projects', fn (Builder $query) => $query->whereKey($project->getKey()))
                ->with(['organization', 'tags'])
        )
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (Asset $asset) => $this->assetRow($asset));

        $canManage = $request->user()?->can('update', $project) ?? false;

        return Inertia::render('Projects/Show', [
            'project' => $this->row($project),
            'organizations' => $this->organizationOptions(),
            'dataTags' => $this->dataTagOptions(),
            'projectAssets' => Inertia::scroll($assets),
            'assetsTotal' => $project->assets()->count(),
            'filters' => $filters->echoBack(),
            // Manager-only: the org's attachable assets for the picker, plus the
            // full set of currently-bound tokens to seed it (the paginated list
            // above only carries the first page).
            'assetOptions' => $canManage ? $this->assetOptions($project->organization_id) : [],
            'selectedAssetTokens' => $canManage ? $project->assets()->pluck('assets.token')->all() : [],
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $this->resolveOrganization($request->validated());
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $project->update($data);
        $project->syncDataTags($tags);

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }

    /**
     * Translate the organization token into its id (never trust ids from the
     * frontend — only tokens cross the wire).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveOrganization(array $data): array
    {
        $data['organization_id'] = Organization::where('token', $data['organization'])->value('id');
        unset($data['organization']);

        return $data;
    }

    /**
     * Selectable organizations for the picker, keyed by token.
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function organizationOptions(): array
    {
        return Organization::query()
            ->orderBy('name')
            ->get(['token', 'name'])
            ->map(fn (Organization $organization) => ['value' => $organization->token, 'label' => $organization->name])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Project $project): array
    {
        return [
            'token' => $project->token,
            'name' => $project->name,
            'description' => $project->description,
            'private' => $project->private,
            'organization' => $project->organization->token,
            'organization_name' => $project->organization->name,
            'tags' => $this->serializeTags($project->tags),
            'record_status' => $project->record_status->value,
            'created_at' => $project->created_at?->toIso8601String(),
        ];
    }
}
