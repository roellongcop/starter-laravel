<?php

namespace App\Jobs;

use App\Enums\UserExportStatus;
use App\Exports\UsersExport;
use App\Models\UserExport;
use App\Notifications\ExportReadyNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

class GenerateExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public function __construct(public UserExport $export, public bool $notify = true) {}

    public function handle(): void
    {
        $this->export->update(['status' => UserExportStatus::Running]);

        try {
            $filters = $this->export->filters ?? [];
            $filename = dated_path("users-{$this->export->id}-".now()->format('Ymd-His').".{$this->export->format}");
            $source = new UsersExport($filters, owner: $this->export->user);

            if ($this->export->format === 'pdf') {
                $users = $source->query()->get();
                $pdf = Pdf::loadView('exports.users-pdf', ['users' => $users]);
                Storage::disk('exports')->put($filename, $pdf->output());
                $rowCount = $users->count();
            } else {
                ExcelFacade::store($source, $filename, 'exports', $this->writerType());
                $rowCount = $source->query()->count();
            }

            $this->export->update([
                'filename' => $filename,
                'row_count' => $rowCount,
                'status' => UserExportStatus::Done,
            ]);

            if ($this->notify) {
                $this->export->user?->notify(new ExportReadyNotification($this->export->fresh()));
            }
        } catch (\Throwable $e) {
            $this->export->update([
                'status' => UserExportStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function writerType(): string
    {
        return match ($this->export->format) {
            'xlsx' => Excel::XLSX,
            'xls' => Excel::XLS,
            default => Excel::CSV,
        };
    }

    public function failed(\Throwable $e): void
    {
        $this->export->update([
            'status' => UserExportStatus::Failed,
            'error_message' => $e->getMessage(),
        ]);
    }
}
