<?php

namespace App\Policies;

class ReferenceFilePolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'reference-files';
    }
}
