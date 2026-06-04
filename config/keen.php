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
    | Files admin upload allowlist
    |--------------------------------------------------------------------------
    |
    | Extensions the Files admin uploader (/files/create) accepts on top of the
    | editable image types (ImageSettings::allowed_types).
    |
    */

    'file_document_extensions' => ['pdf', 'doc', 'docx', 'csv', 'xls', 'xlsx'],

];
