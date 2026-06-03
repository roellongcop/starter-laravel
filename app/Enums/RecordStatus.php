<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Business Active/Inactive toggle stored in the `record_status` tinyint column.
 * This is NOT deletion — the app does not use SoftDeletes.
 */
enum RecordStatus: int
{
    use HasOptions;

    case Active = 1;
    case Inactive = 0;
}
