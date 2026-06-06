<?php

namespace App\Jobs;

use App\Exports\UsersExport;
use App\Models\UserExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Writes ONE export file for a single id-window (≤ keen.export_shard_size rows) to
 * the exports disk under the per-export part directory. FinalizeExportJob zips all
 * parts together once the batch completes.
 */
class ExportShardJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    /**
     * @param  array{0: int, 1: int}  $window  inclusive [low id, high id]
     */
    public function __construct(
        public UserExport $export,
        public array $window,
        public int $part,
        public string $partDir,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $filters = $this->export->filters ?? [];
        $format = $this->export->format;
        $partName = 'part-'.str_pad((string) $this->part, 3, '0', STR_PAD_LEFT).".{$format}";
        $partPath = "{$this->partDir}/{$partName}";

        $source = new UsersExport($filters, $this->window);

        if ($format === 'pdf') {
            $users = $source->query()->get();
            $pdf = Pdf::loadView('exports.users-pdf', ['users' => $users]);
            Storage::disk('exports')->put($partPath, $pdf->output());
            $rowsWritten = $users->count();
        } else {
            ExcelFacade::store($source, $partPath, 'exports', $this->writerType($format));
            $rowsWritten = $source->query()->count();
        }

        $this->export->increment('processed_rows', $rowsWritten);
    }

    protected function writerType(string $format): string
    {
        return match ($format) {
            'xlsx' => Excel::XLSX,
            'xls' => Excel::XLS,
            default => Excel::CSV,
        };
    }
}
