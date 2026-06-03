<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Maps to the typed spatie/laravel-settings groups (Phase 4).
 */
enum SettingType: string
{
    use HasOptions;

    case System = 'System';
    case Email = 'Email';
    case Image = 'Image';
    case Notification = 'Notification';
}
