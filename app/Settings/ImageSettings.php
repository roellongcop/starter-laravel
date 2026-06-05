<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ImageSettings extends Settings
{
    /** File token of the uploaded favicon (null = use the default). */
    public ?string $favicon_token = null;

    /** File token of the uploaded square (icon) logo. */
    public ?string $square_logo_token = null;

    /** File token of the uploaded landscape (wide) logo. */
    public ?string $landscape_logo_token = null;

    public static function group(): string
    {
        return 'image';
    }
}
