<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AuthEvent: string
{
    use HasOptions;

    case Login = 'login';
    case Logout = 'logout';
}
