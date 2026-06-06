<?php

namespace App\Jobs;

use App\Enums\UserExportStatus;
use App\Models\UserExport;
use App\Notifications\ExportReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Stitches the per-shard export files (on the exports disk) into a single zip the
 * owner downloads, cleans up the parts, then marks the export Done and notifies.
 * Mirrors RestoreBackupJob's temp-workdir + recursive-cleanup pattern since the
 * exports disk is typically s3 (SeaweedFS) while ZipArchive needs local files.
 */
class FinalizeExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public UserExport $export,
        public string $partDir,
        public bool $notify = true,
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk('exports');
        $workdir = storage_path('app/export-zip-'.$this->export->id);
        $zipPath = $workdir.'/export.zip';

        try {
            $parts = collect($disk->files($this->partDir))->sort()->values();

            File::ensureDirectoryExists($workdir);

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create export archive.');
            }
            foreach ($parts as $part) {
                $zip->addFromString(basename((string) $part), (string) $disk->get($part));
            }
            $zip->close();

            $dest = dated_path("users-{$this->export->id}-".now()->format('Ymd-His').'.zip');
            $disk->put($dest, (string) file_get_contents($zipPath));

            // Drop the now-zipped parts.
            $disk->deleteDirectory($this->partDir);

            $this->export->update([
                'filename' => $dest,
                'row_count' => $this->export->total_rows ?? $this->export->processed_rows,
                'processed_rows' => $this->export->total_rows ?? $this->export->processed_rows,
                'status' => UserExportStatus::Done,
            ]);

            if ($this->notify) {
                $this->export->user?->notify(new ExportReadyNotification($this->export->fresh()));
            }
        } catch (\Throwable $e) {
            $this->export->update([
                'status' => UserExportStatus::Failed,
                'error_message' => Str::limit($e->getMessage(), 5000, ''),
            ]);

            throw $e;
        } finally {
            File::deleteDirectory($workdir);
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->export->refresh();
        $this->export->update([
            'status' => UserExportStatus::Failed,
            'error_message' => $this->export->error_message
                ?? Str::limit($e->getMessage(), 5000, ''),
        ]);
    }
}
