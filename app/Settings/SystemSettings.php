<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SystemSettings extends Settings
{
    public string $app_name;

    public string $timezone;

    public int $pagination_size;

    public int $auto_logout_seconds;

    public bool $whitelist_ip_only;

    /** light | dark | system */
    public string $default_theme;

    public static function group(): string
    {
        return 'system';
    }
}
