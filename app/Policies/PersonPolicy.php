<?php

namespace App\Policies;

class PersonPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'people';
    }
}
