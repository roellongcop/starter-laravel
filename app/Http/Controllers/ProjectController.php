<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Filters\ProjectAssetFilters;
use App\Filters\ProjectFilters;
use App\Http\Controllers\Concerns\SerializesAssets;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Requests\UpdateProjectStatusRequest;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
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
            'statusOptions' => ProjectStatus::options(),
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

        return back(fallback: route('projects.index'))->with('success', 'Project created.');
    }

    public function show(Request $request, ProjectAssetFilters $filters, Project $project): Response
    {
        $this->authorize('view', $project);

        $project->loadMissing(['organization', 'tags']);

        // The project's bound assets — keyset cursor-paginated and server-side
        // searchable (the set can be large), serialized identically to the Assets
        // module (by reference; a renamed asset reflects here on next load) plus
        // the per-project pivot status. Filters mutate the relation's underlying
        // query in place; pagination then runs through the relation so the pivot
        // (and its select columns) hydrate.
        $relation = $project->assets()->with(['organization', 'tags']);
        $filters->apply($relation->getQuery());

        /** @var CursorPaginator<int, Asset> $paginator */
        $paginator = $relation
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        $assets = $paginator->through(function (Asset $asset): array {
            /** @var Pivot $pivot */
            $pivot = $asset->getRelation('pivot');

            return [
                ...$this->assetRow($asset),
                'status' => $pivot->getAttribute('status'),
            ];
        });

        $canManage = $request->user()?->can('update', $project) ?? false;

        return Inertia::render('Projects/Show', [
            'project' => $this->row($project),
            'statusOptions' => ProjectStatus::options(),
            'projectAssets' => Inertia::scroll($assets),
            'assetsTotal' => $project->assets()->count(),
            'filters' => $filters->echoBack(),
            // Manager-only: the currently-bound asset tokens that seed the async
            // attach picker (its options are fetched on demand from assets.options).
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

    /**
     * Inline status change for a project (from the card/detail dropdown), kept
     * separate from the full update form.
     */
    public function updateStatus(UpdateProjectStatusRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $project);

        $project->update(['status' => $request->validated()['status']]);

        // The inline dropdown posts via axios and expects JSON (no page reload);
        // a plain form submit still gets the flash + redirect.
        if ($request->expectsJson()) {
            return response()->json(['status' => $project->status->value]);
        }

        return back()->with('success', 'Project status updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return back(fallback: route('projects.index'))->with('success', 'Project deleted.');
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
     * @return array<string, mixed>
     */
    protected function row(Project $project): array
    {
        return [
            'token' => $project->token,
            'name' => $project->name,
            'description' => $project->description,
            'private' => $project->private,
            'status' => $project->status->value,
            'organization' => $project->organization->token,
            'organization_name' => $project->organization->name,
            'tags' => $this->serializeTags($project->tags),
            'record_status' => $project->record_status->value,
            'created_at' => $project->created_at?->toIso8601String(),
        ];
    }
}
