<?php

namespace App\Jobs;

use App\Enums\UserImportStatus;
use App\Imports\UsersImport;
use App\Models\UserImport;
use App\Notifications\ImportCompleteNotification;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Coordinator for a large import: reads the (≤10MB-capped) file once, slices the
 * rows into keen.import_shard_size windows, then runs a batch of ImportShardJobs —
 * each validating + upserting its slice — finalized by FinalizeImportJob which
 * merges the per-shard error reports into one CSV. Keeps each job under timeout.
 */
class DispatchImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public function __construct(public UserImport $import, public bool $notify = true) {}

    public function handle(): void
    {
        $this->import->update(['status' => UserImportStatus::Running]);

        $sheets = Excel::toArray(new UsersImport, $this->import->filename, 'imports');
        $rows = $sheets[0] ?? [];
        $total = count($rows);

        $this->import->update(['total' => $total, 'success' => 0, 'failed' => 0]);

        if ($total === 0) {
            $this->import->update(['status' => UserImportStatus::Done]);
            if ($this->notify) {
                $this->import->user?->notify(new ImportCompleteNotification($this->import->fresh()));
            }

            return;
        }

        $size = (int) config('keen.import_shard_size');
        $errorDir = dated_path("import-errors/{$this->import->id}");

        $jobs = [];
        foreach (array_chunk($rows, $size) as $i => $slice) {
            $jobs[] = new ImportShardJob($this->import, $slice, $i * $size, $i + 1, $errorDir);
        }

        $importId = $this->import->id;
        $notify = $this->notify;

        Bus::batch($jobs)
            ->name("import-{$importId}")
            ->then(function (Batch $batch) use ($importId, $errorDir, $notify): void {
                $import = UserImport::withInactive()->find($importId);
                if ($import !== null) {
                    FinalizeImportJob::dispatch($import, $errorDir, $notify);
                }
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($importId): void {
                UserImport::withInactive()->whereKey($importId)
                    ->update(['status' => UserImportStatus::Failed->value]);
            })
            ->dispatch();
    }

    public function failed(\Throwable $e): void
    {
        $this->import->update(['status' => UserImportStatus::Failed]);
    }
}
