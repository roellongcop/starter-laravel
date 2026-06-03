<?php

namespace App\Policies;

/**
 * System-role protection (no delete/rename) is enforced in RoleController, not
 * here — the developer role bypasses policies via Gate::before, so the guard
 * must live outside the gate to be bypass-proof.
 */
class RolePolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'roles';
    }
}
