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
            // Optional target owner — an admin uploading on a user's behalf.
            // Authorization for cross-user uploads is enforced in the controller.
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
