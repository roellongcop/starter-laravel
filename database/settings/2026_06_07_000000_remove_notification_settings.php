<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // The notification templates were never consumed (notifications hardcode
        // their text), so the group has been removed.
        $this->migrator->delete('notification.templates');
    }
};
