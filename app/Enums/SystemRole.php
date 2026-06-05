<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * The fixed, seeded roles (RoleType::System) — one source of truth for the role
 * names used in Gate::before, registration, and seeders.
 * See docs/features/users-roles-permissions.md.
 */
enum SystemRole: string
{
    use HasOptions;

    case Developer = 'developer';
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case User = 'user';
}
