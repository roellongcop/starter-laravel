<?php

namespace App\Policies;

class TeamPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'teams';
    }
}
