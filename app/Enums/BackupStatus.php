<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BackupStatus: string
{
    use HasOptions;

    case Pending = 'Pending';
    case Generating = 'Generating';
    case Generated = 'Generated';
    case Failed = 'Failed';
    case Restoring = 'Restoring';
    case Restored = 'Restored';
    case RestoreFailed = 'RestoreFailed';
}
