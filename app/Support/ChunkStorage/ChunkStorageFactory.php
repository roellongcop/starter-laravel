<?php

namespace App\Support\ChunkStorage;

use App\Models\UploadSession;

/**
 * Resolves the ChunkStorage strategy for a session from the driver it recorded at
 * init — never live config — so an in-flight upload survives a UPLOADS_DISK_DRIVER
 * flip and an S3 session is never completed against a local disk (or vice versa).
 */
class ChunkStorageFactory
{
    public function for(UploadSession $session): ChunkStorage
    {
        return $this->forDriver($session->driver);
    }

    public function forDriver(string $driver): ChunkStorage
    {
        return $driver === 's3'
            ? new S3MultipartChunkStorage
            : new LocalChunkStorage;
    }

    /**
     * The driver the `uploads` disk is configured with right now — recorded onto
     * a session at init time.
     */
    public static function currentDriver(): string
    {
        return (string) config('filesystems.disks.uploads.driver', 'local');
    }
}
