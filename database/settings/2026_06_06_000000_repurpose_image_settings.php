<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // The numeric size fields were never read; allowed types moved to
        // config('keen.image_extensions'). Repurpose the group into brand uploads.
        $this->migrator->delete('image.max_width');
        $this->migrator->delete('image.max_height');
        $this->migrator->delete('image.allowed_types');

        $this->migrator->add('image.favicon_token', null);
        $this->migrator->add('image.square_logo_token', null);
        $this->migrator->add('image.landscape_logo_token', null);
    }
};
