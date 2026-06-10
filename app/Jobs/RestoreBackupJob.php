<?php

namespace App\Jobs;

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Support\RestoreSentinel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Restores the app DB from a spatie backup archive (extract .sql → import via the
 * psql client). DESTRUCTIVE: overwrites the configured app connection.
 * See docs/features/backups-exports-imports.md.
 */
class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public function __construct(public Backup $backup, public ?int $operatorId = null) {}

    public function handle(): void
    {
        // Put the app behind a restore maintenance gate for everyone but the operator.
        RestoreSentinel::put($this->operatorId);
        $this->backup->update(['status' => BackupStatus::Restoring]);

        $workdir = storage_path('app/restore-'.$this->backup->id);
        $zipPath = $workdir.'/backup.zip';

        try {
            File::ensureDirectoryExists($workdir);
            file_put_contents($zipPath, Storage::disk($this->backup->disk)->get($this->backup->filename));

            $sql = $this->extractSqlDump($zipPath, $workdir);
            $this->importSql($sql);

            $this->backup->update(['status' => BackupStatus::Restored]);
        } catch (\Throwable $e) {
            $this->backup->update([
                'status' => BackupStatus::RestoreFailed,
                'error_message' => Str::limit($e->getMessage(), 5000, ''),
            ]);

            throw $e;
        } finally {
            $this->cleanup($workdir);
            RestoreSentinel::clear();
        }
    }

    public function failed(\Throwable $e): void
    {
        RestoreSentinel::clear();
        // The catch block normally records the detailed reason first; only fall
        // back to the bare message if nothing was captured.
        $this->backup->refresh();
        $this->backup->update([
            'status' => BackupStatus::RestoreFailed,
            'error_message' => $this->backup->error_message
                ?? Str::limit($e->getMessage(), 5000, ''),
        ]);
    }

    protected function extractSqlDump(string $zipPath, string $workdir): string
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Could not open backup archive.');
        }

        $sqlEntry = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_ends_with($name, '.sql')) {
                $sqlEntry = $name;
                break;
            }
        }

        if ($sqlEntry === null) {
            $zip->close();
            throw new \RuntimeException('No SQL dump found in archive.');
        }

        $zip->extractTo($workdir, $sqlEntry);
        $zip->close();

        return $workdir.'/'.$sqlEntry;
    }

    protected function importSql(string $sqlPath): void
    {
        /** @var array<string, mixed> $db */
        $db = config('database.connections.'.config('database.default'));

        // The password is passed through PGPASSWORD (not the command line) and
        // -f runs the dump script against the app database. ON_ERROR_STOP=1 makes
        // psql abort + return non-zero on the first failed statement instead of
        // limping to exit 0 and reporting a half-applied restore as a success.
        $result = Process::timeout(600)
            ->env(['PGPASSWORD' => (string) $db['password']])
            ->run(sprintf(
                'psql -v ON_ERROR_STOP=1 -h%s -p%s -U%s -d%s -f %s',
                escapeshellarg((string) $db['host']),
                escapeshellarg((string) $db['port']),
                escapeshellarg((string) $db['username']),
                escapeshellarg((string) $db['database']),
                escapeshellarg($sqlPath),
            ));

        if (! $result->successful()) {
            throw new \RuntimeException('Database import failed: '.$result->errorOutput());
        }
    }

    protected function cleanup(string $workdir): void
    {
        // Recursive: the dump extracts into a nested db-dumps/ subfolder, so a
        // flat unlink+rmdir would leave the directory (and the .sql) behind.
        File::deleteDirectory($workdir);
    }
}
