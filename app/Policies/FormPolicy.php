<?php

namespace App\Policies;

class FormPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'forms';
    }
}
