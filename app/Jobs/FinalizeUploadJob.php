<?php

namespace App\Jobs;

use App\Actions\FinalizeUpload;
use App\Enums\UploadStatus;
use App\Models\UploadSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Runs FinalizeUpload off the request for uploads whose assembly is too heavy to
 * do inline — i.e. concatenating a large file on the local driver. (S3 multipart
 * completes server-side and is finalized synchronously.) The client polls the
 * session status until Done. Mirrors FinalizeExportJob's failure handling.
 */
class FinalizeUploadJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public UploadSession $session) {}

    public function handle(FinalizeUpload $finalize): void
    {
        // FinalizeUpload owns the Assembling → Done/Failed transitions.
        $finalize($this->session);
    }

    public function failed(\Throwable $e): void
    {
        $this->session->refresh();

        if ($this->session->status !== UploadStatus::Done) {
            $this->session->update([
                'status' => UploadStatus::Failed,
                'error_message' => $this->session->error_message
                    ?? Str::limit($e->getMessage(), 5000, ''),
            ]);
        }
    }
}
