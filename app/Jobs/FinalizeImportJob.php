<?php

namespace App\Jobs;

use App\Enums\UserImportStatus;
use App\Models\UserImport;
use App\Notifications\ImportCompleteNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Concatenates the per-shard error CSVs (in row order) into one downloadable
 * report, drops the shard files, then marks the import Done and notifies. Counts
 * (total/success/failed) were already tallied by the coordinator + shards.
 */
class FinalizeImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public UserImport $import,
        public string $errorDir,
        public bool $notify = true,
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk('imports');
        $parts = collect($disk->files($this->errorDir))->sort()->values();

        $reportPath = null;
        if ($parts->isNotEmpty()) {
            $body = $parts->map(fn ($p) => rtrim((string) $disk->get($p), "\n"))->implode("\n");
            $reportPath = dated_path("errors-{$this->import->id}.csv");
            $disk->put($reportPath, "row,email,errors\n".$body."\n");
            $disk->deleteDirectory($this->errorDir);
        }

        $this->import->update([
            'error_report_path' => $reportPath,
            'status' => UserImportStatus::Done,
        ]);

        if ($this->notify) {
            $this->import->user?->notify(new ImportCompleteNotification($this->import->fresh()));
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->import->update(['status' => UserImportStatus::Failed]);
    }
}
