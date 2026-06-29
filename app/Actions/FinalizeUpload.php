<?php

namespace App\Actions;

use App\Enums\UploadStatus;
use App\Models\File;
use App\Models\UploadSession;
use App\Support\ChunkStorage\ChunkStorageFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Terminal step of a resumable upload: assemble the stored chunks into the final
 * object on the `uploads` disk, verify integrity, then turn it into a File the
 * rest of the app already understands (FileViewer, ImageStreamer, gated
 * download/preview). Sets the session's Assembling → Done/Failed status itself.
 */
class FinalizeUpload
{
    public function __construct(private ChunkStorageFactory $factory) {}

    public function __invoke(UploadSession $session): File
    {
        $disk = Storage::disk('uploads');
        $storage = $this->factory->for($session);

        try {
            $session->update(['status' => UploadStatus::Assembling]);

            $storage->assemble($session);

            // Integrity gate: the assembled object must be exactly the size the
            // client declared at init. (Extension was allow-listed at init; the
            // download path is gated and never executed, so we record the mime
            // rather than hard-rejecting on it — SeaweedFS mime detection is
            // unreliable.)
            $actualSize = (int) $disk->size($session->object_key);
            if ($actualSize !== (int) $session->size) {
                throw new \RuntimeException(
                    "Assembled size {$actualSize} does not match the declared size {$session->size}."
                );
            }

            $file = $this->createFile($session, $disk, $actualSize);

            $session->update([
                'status' => UploadStatus::Done,
                'file_id' => $file->id,
            ]);

            // The chunks have been consumed/relocated by assemble(); drop their
            // bookkeeping rows.
            $session->parts()->delete();

            return $file;
        } catch (\Throwable $e) {
            // Roll the half-finished object back so a retry/abort starts clean.
            $disk->delete($session->object_key);
            $session->update([
                'status' => UploadStatus::Failed,
                'error_message' => Str::limit($e->getMessage(), 5000, ''),
            ]);

            throw $e;
        }
    }

    /**
     * Create the File + its medialibrary Media row pointing directly at the
     * already-assembled object, mirroring StoreUploadedFile's column
     * denormalization without re-uploading the bytes.
     */
    private function createFile(UploadSession $session, Filesystem $disk, int $size): File
    {
        $mime = $this->detectMime($session, $disk);

        $file = File::create([
            'original_name' => $session->original_name,
            'tag' => $session->tag,
            'owner_id' => $session->owner_id,
        ]);

        // Bypass medialibrary's FileAdder: its media-library.max_file_size (10 MB)
        // guard would reject every large chunked upload. MediaPathGenerator keys
        // the path off the media's created_at month, so align created_at to the
        // YYYY/MM prefix baked into object_key and getPathRelativeToRoot()
        // resolves back to exactly object_key.
        $created = Carbon::createFromFormat('Y/m', substr($session->object_key, 0, 7));

        $media = new Media;
        $media->uuid = (string) Str::uuid();
        $media->collection_name = File::COLLECTION;
        $media->name = pathinfo($session->original_name, PATHINFO_FILENAME);
        $media->file_name = basename($session->object_key);
        $media->mime_type = $mime;
        $media->disk = 'uploads';
        $media->conversions_disk = 'uploads';
        $media->size = $size;
        $media->manipulations = [];
        $media->custom_properties = [];
        $media->generated_conversions = [];
        $media->responsive_images = [];
        // Setting it as an attribute (rather than the read-only property) also
        // keeps the insert's timestamp logic from overwriting it.
        $media->setAttribute('created_at', ($created ?: now())->startOfMonth());
        $file->media()->save($media);

        $file->update([
            'extension' => $session->extension,
            'mime' => $mime,
            'size' => $size,
            'disk' => 'uploads',
            'path' => $media->getPathRelativeToRoot(),
        ]);

        return $file;
    }

    private function detectMime(UploadSession $session, Filesystem $disk): string
    {
        try {
            $mime = $disk->mimeType($session->object_key);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        } catch (\Throwable) {
            // fall through to the client-declared mime
        }

        return $session->mime ?: 'application/octet-stream';
    }
}
