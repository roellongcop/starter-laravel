<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum UserStatus: string
{
    use HasOptions;

    case Active = 'Active';
    case Blocked = 'Blocked';
    case Inactive = 'Inactive';
}
