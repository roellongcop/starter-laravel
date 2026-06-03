<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum UserExportStatus: string
{
    use HasOptions;

    case Pending = 'Pending';
    case Running = 'Running';
    case Done = 'Done';
    case Failed = 'Failed';
}
