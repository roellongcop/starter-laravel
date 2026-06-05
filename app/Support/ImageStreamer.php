<?php

namespace App\Support;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use League\Glide\ServerFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders + caches an on-demand resized copy of a stored image via league/glide
 * (cached on the local image-cache disk). See docs/features/files-and-media.md.
 */
class ImageStreamer
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function stream(File $file, array $query): StreamedResponse
    {
        abort_unless(str_starts_with((string) $file->mime, 'image/'), 404);
        abort_if($file->path === null, 404);

        $params = ImageParams::sanitize($query);

        $server = ServerFactory::create([
            'source' => Storage::disk($file->disk)->getDriver(),
            'cache' => Storage::disk('image-cache')->getDriver(),
            // Namespace the cache per source disk so identical relative paths on
            // different disks never collide.
            'cache_path_prefix' => $file->disk,
            'driver' => config('media-library.image_driver', 'gd'),
        ]);

        $cachedPath = $server->makeImage($file->path, $params);

        // Derivatives are immutable: a File's stored path is random + never
        // overwritten, and the params are part of the cache key.
        return Storage::disk('image-cache')->response($cachedPath, null, [
            'Content-Type' => $file->mime,
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }
}
