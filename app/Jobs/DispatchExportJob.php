<?php

namespace App\Jobs;

use App\Enums\UserExportStatus;
use App\Exports\UsersExport;
use App\Models\UserExport;
use App\Notifications\ExportReadyNotification;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

/**
 * Coordinator for a large export: splits the (filtered) result set into id-range
 * windows of keen.export_shard_size rows, then runs a batch of ExportShardJobs —
 * one file per window — finalized by FinalizeExportJob which zips them together.
 * Keeping shards small sidesteps the .xls 65,536-row cap and PDF render timeouts.
 */
class DispatchExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public UserExport $export, public bool $notify = true) {}

    public function handle(): void
    {
        $this->export->update(['status' => UserExportStatus::Running]);

        $filters = $this->export->filters ?? [];
        $ids = (new UsersExport($filters))->query()->reorder('id')->pluck('id');
        $total = $ids->count();

        $this->export->update(['total_rows' => $total, 'processed_rows' => 0]);

        // No rows: nothing to zip — complete immediately so the grid resolves.
        if ($total === 0) {
            $this->export->update(['row_count' => 0, 'status' => UserExportStatus::Done]);
            if ($this->notify) {
                $this->export->user?->notify(new ExportReadyNotification($this->export->fresh()));
            }

            return;
        }

        // PDF renders a whole shard in memory (no streaming), so it gets a smaller size.
        $size = $this->export->format === 'pdf'
            ? (int) config('keen.export_pdf_shard_size')
            : (int) config('keen.export_shard_size');
        $partDir = dated_path("export-parts/{$this->export->id}");

        $jobs = $ids->chunk($size)
            ->values()
            ->map(fn ($chunk, $i) => new ExportShardJob(
                $this->export,
                [(int) $chunk->first(), (int) $chunk->last()],
                $i + 1,
                $partDir,
            ))
            ->all();

        $exportId = $this->export->id;
        $notify = $this->notify;

        Bus::batch($jobs)
            ->name("export-{$exportId}")
            ->then(function (Batch $batch) use ($exportId, $partDir, $notify): void {
                $export = UserExport::withInactive()->find($exportId);
                if ($export !== null) {
                    FinalizeExportJob::dispatch($export, $partDir, $notify);
                }
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($exportId): void {
                UserExport::withInactive()->whereKey($exportId)->update([
                    'status' => UserExportStatus::Failed->value,
                    'error_message' => Str::limit($e->getMessage(), 5000, ''),
                ]);
            })
            ->dispatch();
    }

    public function failed(\Throwable $e): void
    {
        $this->export->update([
            'status' => UserExportStatus::Failed,
            'error_message' => Str::limit($e->getMessage(), 5000, ''),
        ]);
    }
}
