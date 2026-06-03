<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * System roles are seeded and protected; Custom roles are user-defined.
 */
enum RoleType: string
{
    use HasOptions;

    case System = 'System';
    case Custom = 'Custom';
}
