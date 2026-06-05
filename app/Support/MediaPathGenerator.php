<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Stores media under `YYYY/MM/` (from the media's created_at, stable across
 * reads); uniqueness comes from the random filename set at upload time.
 * See docs/features/files-and-media.md.
 */
class MediaPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->basePath($media);
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->basePath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->basePath($media).'responsive-images/';
    }

    protected function basePath(Media $media): string
    {
        $date = $media->created_at ?? now();

        return $date->format('Y/m').'/';
    }
}
