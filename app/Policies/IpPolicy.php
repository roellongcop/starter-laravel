<?php

namespace App\Policies;

class IpPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'ips';
    }
}
