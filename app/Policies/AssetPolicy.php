<?php

namespace App\Policies;

class AssetPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'assets';
    }
}
