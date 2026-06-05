<?php

namespace App\Policies;

/**
 * System-role protection (no delete/rename) is enforced in RoleController, not
 * here, to stay bypass-proof against the developer's Gate::before god-mode.
 * See docs/features/users-roles-permissions.md.
 */
class RolePolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'roles';
    }
}
