<?php

namespace Database\Seeders;

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\VisitLog;
use App\Models\Visitor;
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

            $user->notify(new AdminNotification('Welcome to Keen Admin.', NotificationType::Success, '/dashboard'));
            $user->notify(new AdminNotification('A new user registered.', NotificationType::Info, '/users'));
            $user->notify(new AdminNotification('Backup completed.', NotificationType::System));
        });

        // Sample visitor activity (only on a near-empty table).
        if (Visitor::query()->count() === 0) {
            Visitor::factory()
                ->count(8)
                ->create()
                ->each(fn (Visitor $v) => VisitLog::factory()->count(3)->create(['visitor_id' => $v->id]));
        }
    }
}
