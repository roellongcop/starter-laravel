<?php

namespace App\Http\Controllers;

use App\Filters\OrganizationRoleFilters;
use App\Http\Controllers\Concerns\ProvidesOptions;
use App\Http\Requests\StoreOrganizationRoleRequest;
use App\Http\Requests\UpdateOrganizationRoleRequest;
use App\Models\Organization;
use App\Models\OrganizationRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationRoleController extends Controller
{
    use ProvidesOptions;

    public function index(Request $request, OrganizationRoleFilters $filters): Response
    {
        $this->authorize('viewAny', OrganizationRole::class);

        $roles = $filters->apply(OrganizationRole::query()->with('organization'))
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (OrganizationRole $role) => $this->row($role));

        return Inertia::render('OrganizationRoles/Index', [
            'roles' => Inertia::scroll($roles),
            'filters' => $filters->echoBack(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        return $this->optionsResponse(
            $request,
            OrganizationRole::class,
            fn (OrganizationRole $role): array => ['value' => $role->token, 'label' => $role->name],
            organizationScoped: true,
        );
    }

    public function store(StoreOrganizationRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', OrganizationRole::class);

        OrganizationRole::create($this->resolveOrganization($request->validated()));

        return back(fallback: route('organization-roles.index'))->with('success', 'Organization role created.');
    }

    public function show(OrganizationRole $organizationRole): Response
    {
        $this->authorize('view', $organizationRole);

        return Inertia::render('OrganizationRoles/Show', [
            'role' => $this->row($organizationRole->load('organization')),
        ]);
    }

    public function update(UpdateOrganizationRoleRequest $request, OrganizationRole $organizationRole): RedirectResponse
    {
        $this->authorize('update', $organizationRole);

        $organizationRole->update($this->resolveOrganization($request->validated()));

        return back()->with('success', 'Organization role updated.');
    }

    public function destroy(OrganizationRole $organizationRole): RedirectResponse
    {
        $this->authorize('delete', $organizationRole);

        // Restricted on delete: refuse while teams or people still reference it.
        if ($organizationRole->teams()->exists() || $organizationRole->people()->exists()) {
            return back()->with('error', 'This role is still assigned to teams or members.');
        }

        $organizationRole->delete();

        return back(fallback: route('organization-roles.index'))->with('success', 'Organization role deleted.');
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
    protected function row(OrganizationRole $role): array
    {
        return [
            'token' => $role->token,
            'name' => $role->name,
            'description' => $role->description,
            'organization' => $role->organization->token,
            'organization_name' => $role->organization->name,
            'record_status' => $role->record_status->value,
            'created_at' => $role->created_at?->toIso8601String(),
        ];
    }
}
