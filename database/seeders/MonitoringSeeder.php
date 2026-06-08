<?php

namespace Database\Seeders;

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\AdminNotification;
use Illuminate\Database\Seeder;

class MonitoringSeeder extends Seeder
{
    public function run(): void
    {
        // Demo notifications for each seeded login (only if they have none).
        User::query()->whereIn('email', [
            'developer@developer.com',
            'superadmin@superadmin.com',
            'admin@admin.com',
        ])->get()->each(function (User $user): void {
            if ($user->notifications()->exists()) {
                return;
            }

            $user->notify(new AdminNotification('Welcome to RL Studio.', NotificationType::Success, '/dashboard'));
            $user->notify(new AdminNotification('A new user registered.', NotificationType::Info, '/users'));
            $user->notify(new AdminNotification('Backup completed.', NotificationType::System));
        });
    }
}
