<?php

namespace App\Support\ChunkStorage;

use App\Models\UploadSession;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Backs a resumable upload on a local `uploads` disk (no S3 multipart available):
 * each chunk is written to a part file under tmp/<token>/, then stream-
 * concatenated in order into the final object on assemble. Never holds the whole
 * file in memory.
 */
class LocalChunkStorage implements ChunkStorage
{
    public function begin(UploadSession $session): array
    {
        // Parts are written lazily; nothing to set up upfront.
        return [];
    }

    public function putPart(UploadSession $session, int $partNumber, string $body): array
    {
        $this->disk()->put($this->partPath($session, $partNumber), $body);

        return ['etag' => null, 'size' => strlen($body)];
    }

    public function assemble(UploadSession $session): void
    {
        $disk = $this->disk();

        $tmpLocal = (string) tempnam(sys_get_temp_dir(), 'upl');
        $out = fopen($tmpLocal, 'wb');

        try {
            foreach ($session->parts()->orderBy('part_number')->get() as $part) {
                $in = $disk->readStream($this->partPath($session, $part->part_number));
                if ($in === null) {
                    throw new \RuntimeException("Missing part {$part->part_number} for upload {$session->token}.");
                }
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
            fclose($out);
            $out = false;

            $disk->writeStream($session->object_key, fopen($tmpLocal, 'rb'));
        } finally {
            if (is_resource($out)) {
                fclose($out);
            }
            @unlink($tmpLocal);
        }

        $disk->deleteDirectory($this->partsDir($session));
    }

    public function abort(UploadSession $session): void
    {
        $this->disk()->deleteDirectory($this->partsDir($session));
    }

    protected function disk(): Filesystem
    {
        return Storage::disk('uploads');
    }

    protected function partsDir(UploadSession $session): string
    {
        return 'tmp/'.$session->token;
    }

    protected function partPath(UploadSession $session, int $partNumber): string
    {
        return $this->partsDir($session).'/'.str_pad((string) $partNumber, 6, '0', STR_PAD_LEFT).'.part';
    }
}
