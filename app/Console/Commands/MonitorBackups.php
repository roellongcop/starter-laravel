<?php

namespace App\Console\Commands;

use App\Enums\BackupStatus;
use App\Enums\NotificationType;
use App\Enums\SystemRole;
use App\Models\Backup;
use App\Models\User;
use App\Notifications\AdminNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Backup health alert. spatie's backup:monitor can't be used (it scans the disk
 * folder our archives are relocated out of, and is pointed at the wrong disk), so
 * this reads the backups table — the source of truth — and alerts developers in-app
 * when no successful backup has completed within the threshold. Wired into the
 * daily schedule a few hours after the nightly backup.
 */
class MonitorBackups extends Command
{
    protected $signature = 'backups:monitor {--hours= : Override the staleness threshold in hours}';

    protected $description = 'Alert developers if no successful backup completed within the threshold';

    public function handle(): int
    {
        $hours = (int) ($this->option('hours') ?? config('keen.backup_alert_after_hours'));

        $latest = Backup::where('status', BackupStatus::Generated)->latest()->first();

        if ($latest && $latest->created_at && $latest->created_at->greaterThan(now()->subHours($hours))) {
            $this->info("Backups healthy: last successful backup {$latest->created_at->diffForHumans()}.");

            return self::SUCCESS;
        }

        $message = $latest
            ? "No successful database backup in the last {$hours}h (last was {$latest->created_at?->diffForHumans()})."
            : 'No successful database backup has ever completed.';

        $developers = User::role(SystemRole::Developer->value)->get();
        Notification::send($developers, new AdminNotification($message, NotificationType::Warning, route('backups.index')));

        $this->warn($message." Alerted {$developers->count()} developer(s).");

        return self::SUCCESS;
    }
}
