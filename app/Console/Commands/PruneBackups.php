<?php

namespace App\Console\Commands;

use App\Enums\BackupStatus;
use App\Models\Backup;
use Illuminate\Console\Command;

/**
 * Retention cleanup for backups. spatie's backup:clean can't be used: CreateBackupJob
 * relocates archives out of the folder spatie scans (into YYYY/MM/), so the backups
 * table is the source of truth. Deletes Generated/Failed rows (and their archives)
 * older than the retention window, always keeping the most recent Generated backup so
 * prune can never leave the system with none. Wired into the weekly schedule.
 */
class PruneBackups extends Command
{
    protected $signature = 'backups:prune {--days= : Override retention window in days}';

    protected $description = 'Delete backups older than the retention window (keeping the latest generated)';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('keen.backup_keep_days'));
        $cutoff = now()->subDays($days);

        $keepId = Backup::where('status', BackupStatus::Generated)->latest()->value('id');

        $stale = Backup::query()
            ->whereIn('status', [BackupStatus::Generated, BackupStatus::Failed])
            ->where('created_at', '<', $cutoff)
            ->when($keepId, fn ($q) => $q->whereKeyNot($keepId))
            ->get();

        $stale->each(fn (Backup $backup) => $backup->deleteWithFile());

        $this->info("Pruned {$stale->count()} backup(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
