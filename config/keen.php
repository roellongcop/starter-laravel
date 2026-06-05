<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default pagination size
    |--------------------------------------------------------------------------
    |
    | Keyset (cursor) pagination page size for list endpoints. SystemSettings
    | (Phase 4) may override this at runtime.
    |
    */

    'pagination_size' => (int) env('PAGINATION_SIZE', 20),

    /*
    |--------------------------------------------------------------------------
    | Export / import sync thresholds
    |--------------------------------------------------------------------------
    |
    | Jobs at or below the threshold run synchronously (immediate download);
    | larger ones queue (RUNNING→DONE) and notify the owner on completion.
    |
    */

    'export_sync_threshold' => (int) env('EXPORT_SYNC_THRESHOLD', 100),

    'import_sync_threshold' => (int) env('IMPORT_SYNC_THRESHOLD', 100),

    /*
    |--------------------------------------------------------------------------
    | Document upload allowlist
    |--------------------------------------------------------------------------
    |
    | Extensions accepted by the user-documents uploader (Profile → My
    | Documents) and the File::documents() scope. Max size reuses
    | media-library.max_file_size.
    |
    */

    'document_extensions' => ['pdf', 'doc', 'docx'],

    /*
    |--------------------------------------------------------------------------
    | Image upload allowlist
    |--------------------------------------------------------------------------
    |
    | Extensions accepted by image uploads (the <ImagePicker> via /media and the
    | image branch of the Files uploader). A code-level constant, not an editable
    | setting — the upload component declares accepted types.
    |
    */

    'image_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],

    /*
    |--------------------------------------------------------------------------
    | Files admin upload allowlist
    |--------------------------------------------------------------------------
    |
    | Extensions the Files admin uploader (/files/create) accepts on top of the
    | image types (keen.image_extensions).
    |
    */

    'file_document_extensions' => ['pdf', 'doc', 'docx', 'csv', 'xls', 'xlsx'],

];
