<?php

namespace App\Http\Controllers;

use App\Filters\TeamCategoryFilters;
use App\Http\Requests\StoreTeamCategoryRequest;
use App\Http\Requests\UpdateTeamCategoryRequest;
use App\Models\Organization;
use App\Models\TeamCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamCategoryController extends Controller
{
    public function index(Request $request, TeamCategoryFilters $filters): Response
    {
        $this->authorize('viewAny', TeamCategory::class);

        $categories = $filters->apply(TeamCategory::query()->with('organization'))
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (TeamCategory $category) => $this->row($category));

        return Inertia::render('TeamCategories/Index', [
            'categories' => Inertia::scroll($categories),
            'filters' => $filters->echoBack(),
            'organizations' => $this->organizationOptions(),
        ]);
    }

    public function store(StoreTeamCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', TeamCategory::class);

        TeamCategory::create($this->resolveOrganization($request->validated()));

        return redirect()->route('team-categories.index')->with('success', 'Team category created.');
    }

    public function show(TeamCategory $teamCategory): Response
    {
        $this->authorize('view', $teamCategory);

        return Inertia::render('TeamCategories/Show', [
            'category' => $this->row($teamCategory->load('organization')),
            'organizations' => $this->organizationOptions(),
        ]);
    }

    public function update(UpdateTeamCategoryRequest $request, TeamCategory $teamCategory): RedirectResponse
    {
        $this->authorize('update', $teamCategory);

        $teamCategory->update($this->resolveOrganization($request->validated()));

        return back()->with('success', 'Team category updated.');
    }

    public function destroy(TeamCategory $teamCategory): RedirectResponse
    {
        $this->authorize('delete', $teamCategory);

        // Lookups are restricted on delete; surface a friendly message instead of
        // a foreign-key violation when the category is still assigned to teams.
        if ($teamCategory->teams()->exists()) {
            return back()->with('error', 'This category is still used by one or more teams.');
        }

        $teamCategory->delete();

        return redirect()->route('team-categories.index')->with('success', 'Team category deleted.');
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
    protected function row(TeamCategory $category): array
    {
        return [
            'token' => $category->token,
            'name' => $category->name,
            'description' => $category->description,
            'organization' => $category->organization->token,
            'organization_name' => $category->organization->name,
            'record_status' => $category->record_status->value,
            'created_at' => $category->created_at?->toIso8601String(),
        ];
    }
}
