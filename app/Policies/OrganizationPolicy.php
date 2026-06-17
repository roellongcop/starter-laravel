<?php

namespace App\Policies;

class OrganizationPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'organizations';
    }
}
