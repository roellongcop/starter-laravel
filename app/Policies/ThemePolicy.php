<?php

namespace App\Policies;

class ThemePolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'themes';
    }
}
