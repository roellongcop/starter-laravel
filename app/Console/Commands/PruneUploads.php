<?php

namespace App\Console\Commands;

use App\Enums\UploadStatus;
use App\Models\UploadSession;
use App\Support\ChunkStorage\ChunkStorageFactory;
use Illuminate\Console\Command;

/**
 * Cleans up resumable uploads that were started but never completed. Crucially it
 * calls ChunkStorage::abort() first — an S3 multipart upload left open keeps its
 * parts billable and invisible to object listings, so deleting only the DB row
 * would orphan storage. Wired into the hourly schedule.
 */
class PruneUploads extends Command
{
    protected $signature = 'uploads:prune {--hours= : Prune unfinished sessions older than this many hours (default: those past their expires_at)}';

    protected $description = 'Abort and delete expired, unfinished upload sessions';

    public function handle(ChunkStorageFactory $factory): int
    {
        $query = UploadSession::query()->where('status', '!=', UploadStatus::Done);

        if ($this->option('hours') !== null) {
            $query->where('created_at', '<', now()->subHours((int) $this->option('hours')));
        } else {
            $query->where('expires_at', '<', now());
        }

        $stale = $query->get();

        foreach ($stale as $session) {
            try {
                $factory->for($session)->abort($session);
            } catch (\Throwable $e) {
                $this->warn("Failed to abort upload {$session->token}: {$e->getMessage()}");
            }

            // Cascades to upload_session_parts.
            $session->delete();
        }

        $this->info("Pruned {$stale->count()} stale upload session(s).");

        return self::SUCCESS;
    }
}
