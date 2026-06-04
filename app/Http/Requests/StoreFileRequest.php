<?php

namespace App\Http\Requests;

use App\Settings\ImageSettings;

class StoreFileRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowed = array_values(array_unique(array_merge(
            app(ImageSettings::class)->allowed_types,
            config('keen.file_document_extensions'),
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
