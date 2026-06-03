<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Stores media under `YYYY/MM/` (relative to the disk root, i.e. the bucket) so
 * keys look like `uploads-bucket/2026/06/<random>.ext`. The year/month is taken
 * from the media's created_at so the path is stable across reads. Uniqueness is
 * guaranteed by the random file name set at upload time (see StoreUploadedFile).
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
