<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

/**
 * Derives the role JSON the frontend consumes — module_access (button
 * visibility) and main_navigation (sidebar tree) — and merges a user's roles
 * into one menu. See docs/features/users-roles-permissions.md.
 */
class Navigation
{
    /**
     * Group permission names into the module_access map. Standalone abilities
     * (no dot, e.g. "view-inactive") are ignored here.
     *
     * @param  array<int, string>  $permissionNames
     * @return array<string, array<int, string>>
     */
    public static function modulesFor(array $permissionNames): array
    {
        $modules = [];

        foreach ($permissionNames as $name) {
            if (! str_contains($name, '.')) {
                continue;
            }

            [$key, $ability] = explode('.', $name, 2);
            $modules[$key][] = $ability;
        }

        return $modules;
    }

    /**
     * Filter the default sidebar template down to the accessible modules.
     *
     * @param  array<string, array<int, string>>  $modules
     * @return array<int, array<string, mixed>>
     */
    public static function navigationFor(array $modules): array
    {
        $visible = [];

        foreach (self::template() as $item) {
            if (isset($item['children'])) {
                $children = array_values(array_filter(
                    $item['children'],
                    fn (array $child) => isset($modules[$child['key']]),
                ));

                if ($children !== []) {
                    $visible[] = [...$item, 'children' => $children];
                }

                continue;
            }

            if (isset($modules[$item['key']])) {
                $visible[] = $item;
            }
        }

        return $visible;
    }

    /**
     * The full sidebar template (every item). Keys match resource permission keys.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function template(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'LayoutDashboard', 'href' => '/dashboard'],
            [
                'label' => 'Access', 'icon' => 'ShieldCheck', 'children' => [
                    ['key' => 'users', 'label' => 'Users', 'icon' => 'Users', 'href' => '/users'],
                    // ['key' => 'user-meta', 'label' => 'User Meta', 'icon' => 'Tags', 'href' => '/user-meta'],
                    ['key' => 'roles', 'label' => 'Roles', 'icon' => 'KeyRound', 'href' => '/roles'],
                ],
            ],
            [
                'label' => 'SWIFTTT', 'icon' => 'Building2', 'children' => [
                    ['key' => 'organizations', 'label' => 'Organizations', 'icon' => 'Building2', 'href' => '/organizations'],
                    ['key' => 'projects', 'label' => 'Projects', 'icon' => 'FolderKanban', 'href' => '/projects'],
                    ['key' => 'assets', 'label' => 'Assets', 'icon' => 'Boxes', 'href' => '/assets'],
                    ['key' => 'forms', 'label' => 'Forms', 'icon' => 'ClipboardList', 'href' => '/forms'],
                    // Teams & People is a tabbed page (Teams / Team Categories / People);
                    // only the entry point and Organization Roles live in the sidebar.
                    ['key' => 'teams', 'label' => 'Teams and People', 'icon' => 'UsersRound', 'href' => '/teams'],
                    ['key' => 'organization-roles', 'label' => 'Organization Roles', 'icon' => 'BadgeCheck', 'href' => '/organization-roles'],
                    ['key' => 'reference-files', 'label' => 'Reference Files', 'icon' => 'FileText', 'href' => '/reference-files'],
                ],
            ],
            [
                'label' => 'Content', 'icon' => 'FolderOpen', 'children' => [
                    ['key' => 'files', 'label' => 'Files', 'icon' => 'Files', 'href' => '/files'],
                    ['key' => 'themes', 'label' => 'Themes', 'icon' => 'Palette', 'href' => '/themes'],
                    ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'Bell', 'href' => '/notifications'],
                ],
            ],
            [
                'label' => 'Data', 'icon' => 'Database', 'children' => [
                    ['key' => 'exports', 'label' => 'My Exports', 'icon' => 'Download', 'href' => '/exports'],
                    ['key' => 'imports', 'label' => 'My Imports', 'icon' => 'Upload', 'href' => '/imports'],
                    ['key' => 'backups', 'label' => 'Backups', 'icon' => 'Archive', 'href' => '/backups'],
                ],
            ],
            [
                'label' => 'System', 'icon' => 'Settings', 'children' => [
                    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'Settings', 'href' => '/settings'],
                    ['key' => 'ips', 'label' => 'IP Lists', 'icon' => 'Network', 'href' => '/ips'],
                    ['key' => 'sessions', 'label' => 'Sessions', 'icon' => 'MonitorSmartphone', 'href' => '/sessions'],
                    ['key' => 'logs', 'label' => 'Audit Logs', 'icon' => 'ScrollText', 'href' => '/logs'],
                    ['key' => 'login-history', 'label' => 'Login History', 'icon' => 'History', 'href' => '/login-history'],
                    ['key' => 'queue', 'label' => 'Queue', 'icon' => 'ListChecks', 'href' => '/queue'],
                ],
            ],
        ];
    }

    /**
     * Flat palette of the known module items (template leaves) for the menu
     * builder: { key, label, icon, href }.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function catalog(): array
    {
        $leaves = [];

        foreach (self::template() as $item) {
            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    $leaves[] = $child;
                }
            } elseif (isset($item['key'])) {
                $leaves[] = $item;
            }
        }

        return $leaves;
    }

    /**
     * The sidebar tree for a user: the merge of their roles' custom menus (or the
     * permission-derived default for roles without one), intersected with the
     * user's accessible modules so nothing inaccessible ever renders.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forUser(User $user): array
    {
        $modules = self::modulesFor($user->getAllPermissions()->pluck('name')->all());

        /** @var array<int, array<string, mixed>> $merged */
        $merged = [];

        // Highest priority first so it wins label/icon/position on conflicts.
        /** @var Role $role */
        foreach ($user->roles()->with('permissions')->orderByDesc('priority')->orderBy('id')->get() as $role) {
            $menu = is_array($role->main_navigation) && $role->main_navigation !== []
                ? $role->main_navigation
                : self::navigationFor(self::modulesFor($role->permissions->pluck('name')->all()));

            $merged = self::mergeTrees($merged, $menu);
        }

        if ($merged === []) {
            $merged = self::navigationFor($modules);
        }

        return self::intersect($merged, $modules);
    }

    /**
     * Merge $incoming nav nodes into $acc, deduping leaves by key|href across the
     * whole tree and merging groups by label. $acc (higher priority) is built
     * first, so it wins; only not-yet-seen items are appended.
     *
     * @param  array<int, array<string, mixed>>  $acc
     * @param  array<int, array<string, mixed>>  $incoming
     * @return array<int, array<string, mixed>>
     */
    protected static function mergeTrees(array $acc, array $incoming): array
    {
        $seen = self::collectLeafIds($acc);

        foreach ($incoming as $node) {
            if (isset($node['children'])) {
                $fresh = [];
                foreach ($node['children'] as $child) {
                    $id = self::leafId($child);
                    if ($id !== null && ! in_array($id, $seen, true)) {
                        $fresh[] = $child;
                        $seen[] = $id;
                    }
                }

                if ($fresh === []) {
                    continue;
                }

                $existing = null;
                foreach ($acc as $i => $a) {
                    if (isset($a['children']) && ($a['label'] ?? null) === ($node['label'] ?? null)) {
                        $existing = $i;
                        break;
                    }
                }

                if ($existing !== null) {
                    $acc[$existing]['children'] = array_merge($acc[$existing]['children'], $fresh);
                } else {
                    $acc[] = [...$node, 'children' => $fresh];
                }

                continue;
            }

            $id = self::leafId($node);
            if ($id !== null && ! in_array($id, $seen, true)) {
                $acc[] = $node;
                $seen[] = $id;
            }
        }

        return $acc;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     * @return array<int, string>
     */
    protected static function collectLeafIds(array $tree): array
    {
        $ids = [];

        foreach ($tree as $node) {
            if (isset($node['children'])) {
                foreach ($node['children'] as $child) {
                    if (($id = self::leafId($child)) !== null) {
                        $ids[] = $id;
                    }
                }
            } elseif (($id = self::leafId($node)) !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $leaf
     */
    protected static function leafId(array $leaf): ?string
    {
        $id = $leaf['key'] ?? $leaf['href'] ?? null;

        return is_string($id) ? $id : null;
    }

    /**
     * Drop module leaves (those with a `key`) the user can't access, and any
     * group left empty. Link leaves (no key) always survive.
     *
     * @param  array<int, array<string, mixed>>  $tree
     * @param  array<string, array<int, string>>  $modules
     * @return array<int, array<string, mixed>>
     */
    protected static function intersect(array $tree, array $modules): array
    {
        $result = [];

        foreach ($tree as $node) {
            if (isset($node['children'])) {
                $children = array_values(array_filter(
                    $node['children'],
                    fn (array $child) => self::leafVisible($child, $modules),
                ));

                if ($children !== []) {
                    $result[] = [...$node, 'children' => $children];
                }

                continue;
            }

            if (self::leafVisible($node, $modules)) {
                $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $leaf
     * @param  array<string, array<int, string>>  $modules
     */
    protected static function leafVisible(array $leaf, array $modules): bool
    {
        if (! isset($leaf['key'])) {
            return true; // custom/external link — no permission tie
        }

        return isset($modules[$leaf['key']]);
    }
}
