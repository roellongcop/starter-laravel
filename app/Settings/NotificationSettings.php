<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NotificationSettings extends Settings
{
    /**
     * Per-type message templates, keyed by NotificationType case value.
     *
     * @var array<string, string>
     */
    public array $templates;

    public static function group(): string
    {
        return 'notification';
    }
}
