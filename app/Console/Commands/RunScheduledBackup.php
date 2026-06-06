<?php

namespace App\Console\Commands;

use App\Enums\BackupStatus;
use App\Jobs\CreateBackupJob;
use App\Models\Backup;
use Illuminate\Console\Command;

/**
 * Queues a database backup the same way the admin UI does
 * (App\Http\Controllers\BackupController::store) — a Pending row plus a
 * CreateBackupJob — so scheduled backups share the grid, status tracking, and
 * archive relocation. Wired into the nightly schedule in routes/console.php.
 */
class RunScheduledBackup extends Command
{
    protected $signature = 'backups:run';

    protected $description = 'Queue a database backup (same flow as the admin UI)';

    public function handle(): int
    {
        $backup = Backup::create(['status' => BackupStatus::Pending, 'disk' => 'backups']);

        CreateBackupJob::dispatch($backup);

        $this->info("Backup #{$backup->id} queued.");

        return self::SUCCESS;
    }
}
