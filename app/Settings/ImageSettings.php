<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ImageSettings extends Settings
{
    public int $max_width;

    public int $max_height;

    /** @var array<int, string> */
    public array $allowed_types;

    public static function group(): string
    {
        return 'image';
    }
}
