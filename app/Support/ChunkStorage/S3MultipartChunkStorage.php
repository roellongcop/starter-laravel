<?php

namespace App\Support\ChunkStorage;

use App\Models\UploadSession;
use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;

/**
 * Backs a resumable upload with a native S3 multipart upload: each chunk is an
 * S3 UploadPart, and completeMultipartUpload stitches them server-side into the
 * final object — bytes are never re-assembled through PHP. The part ETags S3
 * returns are persisted per part and replayed (ordered) on complete.
 */
class S3MultipartChunkStorage implements ChunkStorage
{
    public function begin(UploadSession $session): array
    {
        $result = $this->client()->createMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $session->object_key,
            'ContentType' => $session->mime ?: 'application/octet-stream',
        ]);

        return ['s3_upload_id' => (string) $result['UploadId']];
    }

    public function putPart(UploadSession $session, int $partNumber, string $body): array
    {
        $result = $this->client()->uploadPart([
            'Bucket' => $this->bucket(),
            'Key' => $session->object_key,
            'UploadId' => $session->s3_upload_id,
            'PartNumber' => $partNumber,
            'Body' => $body,
        ]);

        // Keep the ETag verbatim (quotes included) — completeMultipartUpload must
        // echo back exactly what uploadPart returned.
        return ['etag' => (string) $result['ETag'], 'size' => strlen($body)];
    }

    public function assemble(UploadSession $session): void
    {
        $parts = $session->parts()
            ->orderBy('part_number')
            ->get()
            ->map(fn ($part): array => [
                'PartNumber' => $part->part_number,
                'ETag' => $part->etag,
            ])
            ->all();

        $this->client()->completeMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $session->object_key,
            'UploadId' => $session->s3_upload_id,
            'MultipartUpload' => ['Parts' => $parts],
        ]);
    }

    public function abort(UploadSession $session): void
    {
        if ($session->s3_upload_id === null) {
            return;
        }

        try {
            $this->client()->abortMultipartUpload([
                'Bucket' => $this->bucket(),
                'Key' => $session->object_key,
                'UploadId' => $session->s3_upload_id,
            ]);
        } catch (\Throwable) {
            // The multipart upload is already gone (completed/expired) — nothing
            // left to clean up.
        }
    }

    protected function client(): S3Client
    {
        $disk = Storage::disk('uploads');

        if (! $disk instanceof AwsS3V3Adapter) {
            throw new \RuntimeException('The uploads disk is not an S3 disk; cannot use S3 multipart upload.');
        }

        return $disk->getClient();
    }

    protected function bucket(): string
    {
        return (string) config('filesystems.disks.uploads.bucket');
    }
}
