<?php

namespace App\Jobs;

use App\Enums\UserImportStatus;
use App\Imports\UsersImport;
use App\Models\User;
use App\Models\UserImport;
use App\Notifications\ImportCompleteNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProcessImportJob implements ShouldQueue
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

        try {
            $rows = Excel::toCollection(new UsersImport, $this->import->filename, 'imports')->first()
                ?? collect();

            $success = 0;
            $failures = [];

            foreach ($rows as $i => $row) {
                $data = ['name' => $row['name'] ?? null, 'email' => $row['email'] ?? null];

                $validator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255'],
                ]);

                if ($validator->fails()) {
                    $failures[] = [
                        'row' => $i + 2, // +1 header, +1 to 1-index
                        'email' => $data['email'],
                        'errors' => implode('; ', $validator->errors()->all()),
                    ];

                    continue;
                }

                User::updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => $row['password'] ?? Str::random(16),
                        'user_status' => $row['status'] ?? 'Active',
                    ],
                );
                $success++;
            }

            $reportPath = null;
            if ($failures !== []) {
                $reportPath = "errors-{$this->import->id}.csv";
                Storage::disk('imports')->put($reportPath, $this->toCsv($failures));
            }

            $this->import->update([
                'total' => $rows->count(),
                'success' => $success,
                'failed' => count($failures),
                'error_report_path' => $reportPath,
                'status' => UserImportStatus::Done,
            ]);

            if ($this->notify) {
                $this->import->user?->notify(new ImportCompleteNotification($this->import->fresh()));
            }
        } catch (\Throwable $e) {
            $this->import->update(['status' => UserImportStatus::Failed]);

            throw $e;
        }
    }

    /**
     * @param  array<int, array{row: int, email: ?string, errors: string}>  $failures
     */
    protected function toCsv(array $failures): string
    {
        $lines = ['row,email,errors'];
        foreach ($failures as $f) {
            $lines[] = sprintf('%d,"%s","%s"', $f['row'], $f['email'], str_replace('"', "'", $f['errors']));
        }

        return implode("\n", $lines)."\n";
    }

    public function failed(\Throwable $e): void
    {
        $this->import->update(['status' => UserImportStatus::Failed]);
    }
}
