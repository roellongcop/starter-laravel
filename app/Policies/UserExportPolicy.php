<?php

namespace App\Policies;

class UserExportPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'exports';
    }
}
