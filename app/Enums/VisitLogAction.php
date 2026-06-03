<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VisitLogAction: string
{
    use HasOptions;

    case PageView = 'PageView';
    case Action = 'Action';
    case Download = 'Download';
    case Login = 'Login';
    case Logout = 'Logout';
}
