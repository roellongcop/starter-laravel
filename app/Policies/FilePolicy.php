<?php

namespace App\Policies;

class FilePolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'files';
    }
}
