<?php

/*
|--------------------------------------------------------------------------
| Declared permission registry
|--------------------------------------------------------------------------
|
| The single source of truth for the app's abilities. Permissions are created
| from this list by `php artisan permissions:sync` (run at seed/deploy time —
| NEVER reflected from routes at request time). Each resource ability becomes a
| permission named "{key}.{ability}" (e.g. "users.index"); standalone abilities
| are used verbatim (e.g. "view-inactive").
|
| Roles reference these names; Policies/`can:` middleware check them.
|
*/

return [

    'guard' => 'web',

    // CRUD resources: index / show / create / update / delete
    'crud' => [
        'users',
        'user-meta',
        'roles',
        'files',
        'ips',
        'organizations',
        'projects',
        'assets',
        'themes',
        'notifications',
        'backups',
        'exports',
        'imports',
    ],

    // Read-only resources: index / show only
    'readonly' => [
        'sessions',
        'logs',
        'login-history',
    ],

    // Resources with bespoke ability sets
    'custom' => [
        'dashboard' => ['index', 'search'],
        'settings' => ['index', 'update'],
        'queue' => ['index', 'manage'],
    ],

    'crud_abilities' => ['index', 'show', 'create', 'update', 'delete'],
    'readonly_abilities' => ['index', 'show'],

    // Cross-cutting abilities not tied to a single resource.
    'standalone' => [
        'view-inactive',
    ],

];
