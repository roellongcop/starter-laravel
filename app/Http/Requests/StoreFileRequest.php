<?php

namespace App\Http\Requests;

class StoreFileRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowed = array_values(array_unique(array_merge(
            config('keen.image_extensions'),
            config('keen.file_document_extensions'),
            config('keen.video_extensions'),
        )));
        $maxKb = (int) (config('media-library.max_file_size', 10 * 1024 * 1024) / 1024);

        return [
            'file' => [
                'required',
                'file',
                "max:{$maxKb}",
                'extensions:'.implode(',', $allowed),
            ],
            'tag' => ['nullable', 'string', 'max:255'],
        ];
    }
}
