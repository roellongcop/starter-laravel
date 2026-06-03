<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Support\Navigation;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        $search = trim((string) $request->string('search'));

        $roles = Role::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount('permissions')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Roles/Index', [
            'roles' => cursorResponse($roles, fn (Role $r) => $this->row($r)),
            'filters' => ['search' => $search],
            'can' => [
                'create' => $request->user()->can('roles.create'),
                'update' => $request->user()->can('roles.update'),
                'delete' => $request->user()->can('roles.delete'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('Roles/Create', $this->formOptions());
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = DB::transaction(function () use ($request) {
            /** @var Role $role */
            $role = Role::create([
                'name' => $request->string('name')->toString(),
                'guard_name' => config('permissions.guard', 'web'),
                'description' => $request->input('description'),
                'role_type' => RoleType::Custom,
            ]);

            $this->applyPermissions($role, $request->array('permissions'));

            return $role;
        });

        return redirect()->route('roles.show', $role)->with('success', 'Role created.');
    }

    public function show(Role $role): Response
    {
        $this->authorize('view', $role);

        return Inertia::render('Roles/Show', [
            'role' => $this->row($role, detailed: true),
        ]);
    }

    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        return Inertia::render('Roles/Edit', [
            'role' => $this->row($role, detailed: true),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        // System roles are protected from renaming (bypass-proof: runs even for
        // the developer god-role).
        if ($role->role_type === RoleType::System && $request->string('name')->toString() !== $role->name) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'System roles cannot be renamed.');
        }

        DB::transaction(function () use ($request, $role) {
            $role->update([
                'name' => $request->string('name')->toString(),
                'description' => $request->input('description'),
            ]);

            $this->applyPermissions($role, $request->array('permissions'));
        });

        return redirect()->route('roles.show', $role)->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->role_type === RoleType::System) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'System roles cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }

    /**
     * Sync permissions and re-derive the module_access + main_navigation JSON so
     * the sidebar/button visibility stays in lockstep with grants.
     *
     * @param  array<int, string>  $permissions
     */
    protected function applyPermissions(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);

        $modules = Navigation::modulesFor($permissions);
        $role->forceFill([
            'module_access' => $modules,
            'main_navigation' => Navigation::navigationFor($modules),
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Role $role, bool $detailed = false): array
    {
        $data = [
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'role_type' => $role->role_type?->value,
            'permissions_count' => $role->permissions_count ?? $role->permissions()->count(),
            'created_at' => $role->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['permissions'] = $role->permissions->pluck('name');
            $data['module_access'] = $role->module_access;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        // Resource permissions grouped for checkbox columns, plus standalone.
        $map = Permissions::map();
        $groups = [];

        foreach ($map as $key => $abilities) {
            $groups[$key] = array_map(
                fn (string $ability) => $key === '*' ? $ability : "{$key}.{$ability}",
                $abilities,
            );
        }

        return ['permissionGroups' => $groups];
    }
}
