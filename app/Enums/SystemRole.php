<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * The fixed, seeded roles (RoleType::System). One source of truth for the role
 * names referenced in authorization (Gate::before god-mode), registration, and
 * the seeders — so they can never drift apart by a typo or casing.
 */
enum SystemRole: string
{
    use HasOptions;

    case Developer = 'developer';
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case User = 'user';
}
