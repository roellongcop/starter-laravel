<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum NotificationType: string
{
    use HasOptions;

    case Info = 'Info';
    case Success = 'Success';
    case Warning = 'Warning';
    case Error = 'Error';
    case System = 'System';
}
