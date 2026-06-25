<?php

namespace App\Policies;

class DataTagPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'data-tags';
    }
}
