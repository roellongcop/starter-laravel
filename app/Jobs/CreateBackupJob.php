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
use Illuminate\Support\Str;

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
            // Artisan::call returns the exit code but does NOT throw on a failed
            // backup:run, so check it and surface the console output as the reason.
            $exit = Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);
            $output = trim(Artisan::output());

            if ($exit !== 0) {
                throw new \RuntimeException("backup:run failed (exit {$exit}).\n".$output);
            }

            $disk = Storage::disk('backups');
            // spatie writes into a folder named after config('backup.backup.name');
            // the newest .zip there is the archive we just made.
            $srcDir = (string) config('backup.backup.name');
            $latest = collect($disk->files($srcDir))
                ->filter(fn (string $f) => str_ends_with($f, '.zip'))
                ->sortByDesc(fn (string $f) => $disk->lastModified($f))
                ->first();

            if (! $latest) {
                throw new \RuntimeException("Backup archive not found on disk.\n".$output);
            }

            // Relocate under YYYY/MM/ (keeping the readable timestamped name) so
            // backups follow the same date-foldered layout as uploads.
            $dest = dated_path(basename($latest));
            $disk->move($latest, $dest);

            $this->backup->update([
                'filename' => $dest,
                'disk' => 'backups',
                'size' => $disk->size($dest),
                'status' => BackupStatus::Generated,
            ]);
        } catch (\Throwable $e) {
            $this->backup->update([
                'status' => BackupStatus::Failed,
                'error_message' => Str::limit($e->getMessage(), 5000, ''),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        // Safety net: the catch block above normally records the detailed reason
        // first; only fall back to the bare message if nothing was captured.
        $this->backup->refresh();
        $this->backup->update([
            'status' => BackupStatus::Failed,
            'error_message' => $this->backup->error_message
                ?? Str::limit($e->getMessage(), 5000, ''),
        ]);
    }
}
