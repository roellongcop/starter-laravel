<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum IpListType: string
{
    use HasOptions;

    case Whitelist = 'Whitelist';
    case Blacklist = 'Blacklist';
}
