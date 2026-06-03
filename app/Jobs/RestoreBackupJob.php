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
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Restores the app database from a spatie backup archive by extracting the .sql
 * dump and importing it via the mysql client.
 *
 * DESTRUCTIVE: overwrites the current database. Restores only the configured app
 * connection.
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
            @mkdir($workdir, 0775, true);
            file_put_contents($zipPath, Storage::disk($this->backup->disk)->get($this->backup->filename));

            $sql = $this->extractSqlDump($zipPath, $workdir);
            $this->importSql($sql);

            $this->backup->update(['status' => BackupStatus::Restored]);
        } catch (\Throwable $e) {
            $this->backup->update(['status' => BackupStatus::RestoreFailed]);

            throw $e;
        } finally {
            $this->cleanup($workdir);
            RestoreSentinel::clear();
        }
    }

    public function failed(\Throwable $e): void
    {
        RestoreSentinel::clear();
        $this->backup->update(['status' => BackupStatus::RestoreFailed]);
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

        $result = Process::timeout(600)->run(sprintf(
            'mysql -h%s -P%s -u%s -p%s %s < %s',
            escapeshellarg((string) $db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg((string) $db['username']),
            escapeshellarg((string) $db['password']),
            escapeshellarg((string) $db['database']),
            escapeshellarg($sqlPath),
        ));

        if (! $result->successful()) {
            throw new \RuntimeException('Database import failed: '.$result->errorOutput());
        }
    }

    protected function cleanup(string $workdir): void
    {
        if (! is_dir($workdir)) {
            return;
        }

        foreach ((array) glob($workdir.'/*') as $file) {
            @unlink((string) $file);
        }
        @rmdir($workdir);
    }
}
