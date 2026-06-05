<?php

namespace App\Actions;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * The single write path for uploads: creates a File (medialibrary, private
 * `uploads` disk) and denormalizes the Media metadata onto its columns.
 * See docs/features/files-and-media.md.
 */
class StoreUploadedFile
{
    public function __invoke(UploadedFile $upload, int $ownerId, ?string $tag = null): File
    {
        $file = File::create([
            'original_name' => $upload->getClientOriginalName(),
            'tag' => $tag,
            'owner_id' => $ownerId,
        ]);

        $ext = strtolower($upload->getClientOriginalExtension() ?: ($upload->guessExtension() ?? 'bin'));

        // Store on the private `uploads` disk (never the medialibrary default)
        // under a random unique name so files never collide — the path generator
        // places it at YYYY/MM/<random>.ext.
        $media = $file
            ->addMedia($upload)
            ->usingFileName(Str::random(40).'.'.$ext)
            ->toMediaCollection(File::COLLECTION, 'uploads');

        $file->update([
            'extension' => $media->extension,
            'mime' => $media->mime_type,
            'size' => $media->size,
            'disk' => $media->disk,
            'path' => $media->getPathRelativeToRoot(),
        ]);

        return $file;
    }
}
