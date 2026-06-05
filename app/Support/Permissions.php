<?php

namespace App\Support;

/**
 * Expands the declared registry in config/permissions.php into concrete
 * permission names (sync command, seeders, Navigation).
 * See docs/features/users-roles-permissions.md.
 */
class Permissions
{
    /**
     * Every ability, keyed by resource: ['users' => ['index', ...], ...].
     * Standalone abilities live under the '*' key as bare names.
     *
     * @return array<string, array<int, string>>
     */
    public static function map(): array
    {
        $map = [];

        foreach ((array) config('permissions.crud') as $key) {
            $map[$key] = config('permissions.crud_abilities');
        }

        foreach ((array) config('permissions.readonly') as $key) {
            $map[$key] = config('permissions.readonly_abilities');
        }

        foreach ((array) config('permissions.custom') as $key => $abilities) {
            $map[$key] = $abilities;
        }

        $map['*'] = (array) config('permissions.standalone');

        return $map;
    }

    /**
     * Flat list of every permission name (e.g. "users.index", "view-inactive").
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        $names = [];

        foreach (self::map() as $key => $abilities) {
            foreach ($abilities as $ability) {
                $names[] = $key === '*' ? $ability : "{$key}.{$ability}";
            }
        }

        return $names;
    }

    /**
     * All permission names for the given resource keys (plus optional standalone).
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    public static function forResources(array $keys, bool $withStandalone = false): array
    {
        $map = self::map();
        $names = [];

        foreach ($keys as $key) {
            foreach ($map[$key] ?? [] as $ability) {
                $names[] = "{$key}.{$ability}";
            }
        }

        if ($withStandalone) {
            $names = array_merge($names, $map['*']);
        }

        return $names;
    }

    /** Resource keys that have at least one ability (excludes standalone). */
    public static function resourceKeys(): array
    {
        return array_values(array_filter(array_keys(self::map()), fn ($k) => $k !== '*'));
    }
}
