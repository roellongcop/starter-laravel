<?php

namespace App\Http\Controllers;

use App\Filters\ProjectFilters;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectFilters $filters): Response
    {
        $this->authorize('viewAny', Project::class);

        $projects = $filters->apply(Project::query()->with('organization'))
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
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        Project::create($this->resolveOrganization($request->validated()));

        return redirect()->route('projects.index')->with('success', 'Project created.');
    }

    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('Projects/Show', [
            'project' => $this->row($project->load('organization')),
            'organizations' => $this->organizationOptions(),
        ]);
    }

    /**
     * Show a project nested under its organization
     * (organizations/{organization}/projects/{project}). Renders the same page
     * as show(), but with the breadcrumb trail rooted at the organization.
     */
    public function showForOrganization(Organization $organization, Project $project): Response
    {
        abort_unless($project->organization_id === $organization->id, 404);

        $this->authorize('view', $project);

        return Inertia::render('Projects/Show', [
            'project' => $this->row($project->load('organization')),
            'organizations' => $this->organizationOptions(),
            'parentOrganization' => [
                'token' => $organization->token,
                'name' => $organization->name,
            ],
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($this->resolveOrganization($request->validated()));

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'process' => ['required', 'in:active,in_active,delete'],
            'tokens' => ['required', 'array'],
            'tokens.*' => ['string'],
        ]);

        $this->authorize($validated['process'] === 'delete' ? 'delete' : 'update', Project::class);

        $count = Project::bulkAction($validated['process'], $validated['tokens']);

        return back()->with('success', "{$count} project(s) updated.");
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
            'record_status' => $project->record_status->value,
            'created_at' => $project->created_at?->toIso8601String(),
        ];
    }
}
