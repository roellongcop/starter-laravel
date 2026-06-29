<?php

namespace App\Policies;

class TaskPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'tasks';
    }
}
