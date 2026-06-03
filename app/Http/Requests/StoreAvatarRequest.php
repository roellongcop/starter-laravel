<?php

namespace App\Http\Requests;

use App\Models\File;
use Closure;

/**
 * Validates a profile-photo selection: a file_id pointing at one of the
 * authenticated user's previously uploaded images. The image bytes are uploaded
 * separately via the generic /media endpoint (by the <ImagePicker>), so this
 * request only ever receives an id.
 */
class StoreAvatarRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file_id' => [
                'required',
                'integer',
                'exists:tbl_files,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $file = File::find($value);

                    if ($file === null || $file->owner_id !== $this->user()?->id) {
                        $fail('That photo does not belong to you.');

                        return;
                    }

                    if (! str_starts_with((string) $file->mime, 'image/')) {
                        $fail('That file is not an image.');
                    }
                },
            ],
        ];
    }
}
