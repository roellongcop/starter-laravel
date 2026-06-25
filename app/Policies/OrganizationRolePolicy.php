<?php

namespace App\Policies;

class OrganizationRolePolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'organization-roles';
    }
}
