<?php

namespace App\Policies;

class UserImportPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'imports';
    }
}
