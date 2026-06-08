<?php

use App\Enums\NotificationType;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // System
        $this->migrator->add('system.app_name', 'RL Studio');
        $this->migrator->add('system.timezone', 'UTC');
        $this->migrator->add('system.pagination_size', 20);
        $this->migrator->add('system.auto_logout_seconds', 0);
        $this->migrator->add('system.whitelist_ip_only', false);
        $this->migrator->add('system.default_theme', 'system');

        // Email
        $this->migrator->add('email.from_address', 'hello@example.com');
        $this->migrator->add('email.from_name', 'RL Studio');
        // Empty by default: the EmailSettings override stays dormant and env
        // (MAIL_HOST=mailpit in dev) drives mail until an admin configures real SMTP.
        $this->migrator->add('email.smtp_host', '');
        $this->migrator->add('email.smtp_port', 2525);
        $this->migrator->add('email.smtp_username', null);
        $this->migrator->addEncrypted('email.smtp_password', null);
        $this->migrator->add('email.smtp_encryption', null);

        // Image
        $this->migrator->add('image.max_width', 2000);
        $this->migrator->add('image.max_height', 2000);
        $this->migrator->add('image.allowed_types', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

        // Notification — a default template per type.
        $templates = [];
        foreach (NotificationType::cases() as $type) {
            $templates[$type->value] = "{$type->value}: :message";
        }
        $this->migrator->add('notification.templates', $templates);
    }
};
