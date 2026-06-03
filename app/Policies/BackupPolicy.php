<?php

namespace App\Policies;

class BackupPolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'backups';
    }
}
