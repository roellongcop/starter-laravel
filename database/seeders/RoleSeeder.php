<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Role;
use App\Support\Navigation;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $guard = (string) config('permissions.guard', 'web');

        // developer & superadmin: every permission. (developer also bypasses all
        // gates via Gate::before, but we grant explicitly too for clarity.)
        $all = Permissions::all();

        $this->makeRole('developer', 'Full system access (god mode).', $all, $guard);
        $this->makeRole('superadmin', 'Manages the entire application.', $all, $guard);
        $this->makeRole('admin', 'Day-to-day administration.', $this->adminPermissions(), $guard);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function makeRole(string $name, string $description, array $permissions, string $guard): void
    {
        /** @var Role $role */
        $role = Role::findOrCreate($name, $guard);
        $role->syncPermissions($permissions);

        $modules = Navigation::modulesFor($permissions);

        $role->forceFill([
            'role_type' => RoleType::System,
            'description' => $description,
            'module_access' => $modules,
            'main_navigation' => Navigation::navigationFor($modules),
        ])->save();
    }

    /**
     * Mid-tier admin: read everywhere, plus full control of content/data modules.
     *
     * @return array<int, string>
     */
    protected function adminPermissions(): array
    {
        $map = Permissions::map();
        $names = [];

        // index/show on every resource the user can at least read.
        foreach ($map as $key => $abilities) {
            if ($key === '*') {
                continue;
            }
            foreach (['index', 'show'] as $ability) {
                if (in_array($ability, $abilities, true)) {
                    $names[] = "{$key}.{$ability}";
                }
            }
        }

        // Full CRUD on the modules an admin owns end-to-end.
        $names = array_merge($names, Permissions::forResources(
            ['files', 'themes', 'notifications', 'exports', 'imports'],
        ));

        $names[] = 'dashboard.search';

        return array_values(array_unique($names));
    }
}
