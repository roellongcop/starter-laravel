<?php

namespace App\Http\Controllers;

use App\Filters\OrganizationFilters;
use App\Http\Controllers\Concerns\ProvidesOptions;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    use ProvidesOptions;

    public function index(Request $request, OrganizationFilters $filters): Response
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = $filters->apply(Organization::query()->with('pointOfContact'))
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (Organization $organization) => $this->row($organization));

        return Inertia::render('Organizations/Index', [
            // Inertia::scroll() merges (appends) the paginator's `data` wrapper on
            // partial reloads, driving the <InfiniteScroll> card grid; cursor
            // metadata is derived from the CursorPaginator automatically.
            'organizations' => Inertia::scroll($organizations),
            'filters' => $filters->echoBack(),
        ]);
    }

    /**
     * Search-as-you-type options for the async organization picker (filters +
     * forms across modules). Cursor-paginated `keen.options_limit` rows at a time
     * (ordered by name) so the picker loads more on scroll and the payload never
     * grows with the table — `?q=` narrows by name, `?cursor=` fetches the next
     * page, while `?tokens[]=` rehydrates already-selected values by their public
     * token. Org names are already exposed via every module's filter, so any
     * authenticated user may resolve them.
     */
    public function options(Request $request): JsonResponse
    {
        return $this->optionsResponse(
            $request,
            Organization::class,
            fn (Organization $organization): array => [
                'value' => $organization->token,
                'label' => $organization->name,
            ],
        );
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $this->authorize('create', Organization::class);

        Organization::create($this->resolveContact($request->validated()));

        return back(fallback: route('organizations.index'))->with('success', 'Organization created.');
    }

    public function show(Organization $organization): Response
    {
        $this->authorize('view', $organization);

        // Projects/assets are not loaded here: the page links out to their index
        // pages (pre-filtered by ?organization=), so this view stays lightweight.
        return Inertia::render('Organizations/Show', [
            'organization' => $this->row($organization->load('pointOfContact')),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);

        $organization->update($this->resolveContact($request->validated()));

        return back()->with('success', 'Organization updated.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return back(fallback: route('organizations.index'))->with('success', 'Organization deleted.');
    }

    /**
     * Translate the point-of-contact user token into its id (never trust ids
     * from the frontend — only tokens cross the wire).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveContact(array $data): array
    {
        $token = $data['point_of_contact'] ?? null;
        $data['point_of_contact_id'] = $token ? User::where('token', $token)->value('id') : null;
        unset($data['point_of_contact']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Organization $organization): array
    {
        return [
            'token' => $organization->token,
            'name' => $organization->name,
            'description' => $organization->description,
            'point_of_contact' => $organization->pointOfContact?->token,
            'point_of_contact_name' => $organization->pointOfContact?->name,
            'record_status' => $organization->record_status->value,
            'created_at' => $organization->created_at?->toIso8601String(),
        ];
    }
}
