<?php

namespace App\Jobs;

use App\Enums\BackupStatus;
use App\Models\Backup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public function __construct(public Backup $backup) {}

    public function handle(): void
    {
        $this->backup->update(['status' => BackupStatus::Generating]);

        try {
            Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);

            $disk = Storage::disk('backups');
            // The newest .zip on the backups disk is the archive we just made.
            $latest = collect($disk->allFiles())
                ->filter(fn (string $f) => str_ends_with($f, '.zip'))
                ->sortByDesc(fn (string $f) => $disk->lastModified($f))
                ->first();

            if (! $latest) {
                throw new \RuntimeException('Backup archive not found on disk.');
            }

            $this->backup->update([
                'filename' => $latest,
                'disk' => 'backups',
                'size' => $disk->size($latest),
                'status' => BackupStatus::Generated,
            ]);
        } catch (\Throwable $e) {
            $this->backup->update(['status' => BackupStatus::Failed]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->backup->update(['status' => BackupStatus::Failed]);
    }
}
