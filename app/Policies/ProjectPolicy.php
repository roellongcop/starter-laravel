<?php

namespace App\Policies;

class ProjectPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'projects';
    }
}
