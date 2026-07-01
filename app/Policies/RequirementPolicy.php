<?php

namespace App\Policies;

class RequirementPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'requirements';
    }
}
