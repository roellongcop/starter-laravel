<?php

namespace App\Support\ChunkStorage;

use App\Models\UploadSession;

/**
 * Strategy for storing the chunks of a resumable upload and assembling them into
 * the final object on the `uploads` disk. Two implementations back it:
 * S3 multipart (chunks become S3 parts, S3 stitches them) and local (chunks are
 * part files, concatenated on assemble). The concrete one is chosen by
 * ChunkStorageFactory from the session's recorded driver.
 */
interface ChunkStorage
{
    /**
     * Begin the underlying upload. Returns provider state to persist on the
     * session (e.g. ['s3_upload_id' => '...']); empty for the local driver.
     *
     * @return array<string, string>
     */
    public function begin(UploadSession $session): array;

    /**
     * Durably store one chunk. Returns the part's ETag (null on local) and byte
     * size, which the caller records in upload_session_parts only after this
     * returns successfully.
     *
     * @return array{etag: ?string, size: int}
     */
    public function putPart(UploadSession $session, int $partNumber, string $body): array;

    /**
     * Assemble all stored parts into the final object at $session->object_key on
     * the `uploads` disk and clean up the intermediate parts.
     */
    public function assemble(UploadSession $session): void;

    /**
     * Abort the upload and clean up any intermediate parts (S3
     * abortMultipartUpload / delete the local part dir). Idempotent.
     */
    public function abort(UploadSession $session): void;
}
