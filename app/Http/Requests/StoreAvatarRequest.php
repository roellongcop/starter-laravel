<?php

namespace App\Http\Requests;

use App\Models\File;
use Closure;

/**
 * Validates a profile-photo selection: a file_token pointing at one of the
 * authenticated user's previously uploaded images. The image bytes are uploaded
 * separately via the generic /media endpoint (by the <ImagePicker>), so this
 * request only ever receives the file's public token.
 */
class StoreAvatarRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file_token' => [
                'required',
                'string',
                'exists:files,token',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $file = File::where('token', $value)->first();

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
