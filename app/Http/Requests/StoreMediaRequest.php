<?php

namespace App\Http\Requests;

/**
 * Validates a generic image upload posted by the <ImagePicker> (cropped blobs
 * from upload/camera/existing). Mirrors StoreFileRequest's image rules.
 */
class StoreMediaRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowed = config('keen.image_extensions');
        $maxKb = (int) (config('media-library.max_file_size', 10 * 1024 * 1024) / 1024);

        return [
            'file' => [
                'required',
                'image',
                "max:{$maxKb}",
                'extensions:'.implode(',', $allowed),
            ],
            'tag' => ['nullable', 'string', 'max:255'],
        ];
    }
}
