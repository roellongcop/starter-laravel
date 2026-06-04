<?php

namespace App\Http\Requests;

/**
 * Validates a document upload (pdf/doc/docx) posted by the <FileDropzone>.
 * The allowlist lives in config('keen.document_extensions'); max size reuses
 * the medialibrary limit.
 */
class StoreDocumentRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowed = config('keen.document_extensions', ['pdf', 'doc', 'docx']);
        $maxKb = (int) (config('media-library.max_file_size', 10 * 1024 * 1024) / 1024);

        return [
            'file' => [
                'required',
                'file',
                "max:{$maxKb}",
                'mimes:'.implode(',', $allowed),
                'extensions:'.implode(',', $allowed),
            ],
            'tag' => ['nullable', 'string', 'max:255'],
            // Optional target owner (by token) — an admin uploading on a user's
            // behalf. Cross-user authorization is enforced in the controller.
            'user_token' => ['nullable', 'string', 'exists:users,token'],
        ];
    }
}
