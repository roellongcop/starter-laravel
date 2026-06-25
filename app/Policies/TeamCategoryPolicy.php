<?php

namespace App\Policies;

class TeamCategoryPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'team-categories';
    }
}
