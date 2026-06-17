<?php

namespace App\Http\Controllers;

use App\Filters\OrganizationFilters;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request, OrganizationFilters $filters): Response
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = $filters->apply(Organization::query()->with('pointOfContact'))
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Organizations/Index', [
            'organizations' => cursorResponse($organizations, fn (Organization $organization) => $this->row($organization)),
            'filters' => $filters->echoBack(),
            'users' => $this->userOptions(),
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $this->authorize('create', Organization::class);

        Organization::create($this->resolveContact($request->validated()));

        return redirect()->route('organizations.index')->with('success', 'Organization created.');
    }

    public function show(Organization $organization): Response
    {
        $this->authorize('view', $organization);

        return Inertia::render('Organizations/Show', [
            'organization' => $this->row($organization->load('pointOfContact')),
            'users' => $this->userOptions(),
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

        return redirect()->route('organizations.index')->with('success', 'Organization deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'process' => ['required', 'in:active,in_active,delete'],
            'tokens' => ['required', 'array'],
            'tokens.*' => ['string'],
        ]);

        $this->authorize($validated['process'] === 'delete' ? 'delete' : 'update', Organization::class);

        $count = Organization::bulkAction($validated['process'], $validated['tokens']);

        return back()->with('success', "{$count} organization(s) updated.");
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
     * Selectable users for the point-of-contact picker, keyed by token.
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function userOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['token', 'name'])
            ->map(fn (User $user) => ['value' => $user->token, 'label' => $user->name])
            ->all();
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
