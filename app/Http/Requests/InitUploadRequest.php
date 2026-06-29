<?php

namespace App\Http\Requests;

use Closure;

/**
 * Validates the metadata that opens a resumable upload session. The bytes arrive
 * later as chunks, so here we only gate the declared name (and its extension),
 * total size, and optional mime/tag. Declared values are advisory — assembled
 * size is re-verified on finalize.
 */
class InitUploadRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxBytes = (int) config('keen.max_upload_size');

        return [
            'original_name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $ext = strtolower(pathinfo((string) $value, PATHINFO_EXTENSION));
                    if (! in_array($ext, $this->allowedExtensions(), true)) {
                        $fail('This file type is not allowed.');
                    }
                },
            ],
            'size' => ['required', 'integer', 'min:1', "max:{$maxBytes}"],
            'mime' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The same allowlist the single-shot Files uploader uses (StoreFileRequest).
     *
     * @return array<int, string>
     */
    private function allowedExtensions(): array
    {
        return array_values(array_unique(array_merge(
            config('keen.image_extensions'),
            config('keen.file_document_extensions'),
            config('keen.video_extensions'),
        )));
    }
}
