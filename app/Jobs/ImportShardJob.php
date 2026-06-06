<?php

namespace App\Jobs;

use App\Imports\UsersImport;
use App\Models\UserImport;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Validates + upserts one slice (≤ keen.import_shard_size rows) of an import, then
 * atomically bumps the import's success/failed counts. Failures for the slice are
 * written to a header-less per-shard CSV that FinalizeImportJob concatenates into
 * one report. Each row is validated + upserted via UsersImport::importRow (shared
 * with the sync ProcessImportJob), keyed on email with the model's casts intact.
 */
class ImportShardJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        public UserImport $import,
        public array $rows,
        public int $baseRow,
        public int $part,
        public string $errorDir,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $success = 0;
        $failures = [];

        foreach ($this->rows as $i => $row) {
            $errors = UsersImport::importRow($row);

            if ($errors !== []) {
                $failures[] = [
                    'row' => $this->baseRow + $i + 2, // +1 header, +1 to 1-index
                    'email' => $row['email'] ?? null,
                    'errors' => implode('; ', $errors),
                ];

                continue;
            }

            $success++;
        }

        if ($failures !== []) {
            $partName = 'part-'.str_pad((string) $this->part, 3, '0', STR_PAD_LEFT).'.csv';
            Storage::disk('imports')->put("{$this->errorDir}/{$partName}", $this->toCsv($failures));
        }

        // Atomic so parallel shard workers don't clobber each other's tallies.
        if ($success > 0) {
            $this->import->increment('success', $success);
        }
        if ($failures !== []) {
            $this->import->increment('failed', count($failures));
        }
    }

    /**
     * Header-less rows; FinalizeImportJob prepends the single shared header.
     *
     * @param  array<int, array{row: int, email: ?string, errors: string}>  $failures
     */
    protected function toCsv(array $failures): string
    {
        $lines = [];
        foreach ($failures as $f) {
            $lines[] = sprintf('%d,"%s","%s"', $f['row'], $f['email'], str_replace('"', "'", $f['errors']));
        }

        return implode("\n", $lines)."\n";
    }
}
