<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EmailSettings extends Settings
{
    public string $from_address;

    public string $from_name;

    public string $smtp_host;

    public int $smtp_port;

    public ?string $smtp_username;

    public ?string $smtp_password;

    public ?string $smtp_encryption;

    public static function group(): string
    {
        return 'email';
    }

    /**
     * Secrets should not be exposed when the settings object is serialized to
     * the frontend.
     *
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['smtp_password'];
    }
}
