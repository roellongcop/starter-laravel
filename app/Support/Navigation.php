<?php

namespace App\Support;

/**
 * Derives the two role JSON blobs the frontend consumes:
 *
 * - module_access: { resourceKey: [abilities] } — built from a permission-name
 *   list; React uses it to show/hide buttons (e.g. an Edit button needs
 *   module_access.users to include "update").
 * - main_navigation: the sidebar tree, the default template filtered down to the
 *   modules the role can access (a group survives if any child does).
 *
 * Items use plain href paths (not Ziggy route names) so the sidebar renders even
 * before a resource's routes exist; links go live as later phases add them.
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
                    ['key' => 'visitors', 'label' => 'Visitors', 'icon' => 'Footprints', 'href' => '/visitors'],
                    ['key' => 'visit-logs', 'label' => 'Visit Logs', 'icon' => 'Route', 'href' => '/visit-logs'],
                    ['key' => 'queue', 'label' => 'Queue', 'icon' => 'ListChecks', 'href' => '/queue'],
                ],
            ],
        ];
    }
}
