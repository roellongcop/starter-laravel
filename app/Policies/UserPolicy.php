<?php

namespace App\Policies;

class UserPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'users';
    }
}
