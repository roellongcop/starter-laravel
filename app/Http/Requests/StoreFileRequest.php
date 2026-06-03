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
        $allowed = app(ImageSettings::class)->allowed_types;
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
