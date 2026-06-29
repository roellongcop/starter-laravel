<?php

namespace App\Policies;

class MilestonePolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'milestones';
    }
}
