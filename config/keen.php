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
    | Export / import shard sizes
    |--------------------------------------------------------------------------
    |
    | Queued (above-threshold) jobs are split into shards of this many rows, each
    | processed by its own batched job. Export shards become one file apiece and
    | are zipped together on completion; import shards validate + upsert their
    | slice. Keeping shards small (≤ ~5k) sidesteps the .xls 65,536-row format cap
    | and keeps each job well under its timeout. PDF uses a smaller dedicated size
    | (export_pdf_shard_size) because DomPDF renders a whole shard in memory rather
    | than streaming it, so a 5k-row shard would run long enough to be re-attempted.
    |
    */

    'export_shard_size' => (int) env('EXPORT_SHARD_SIZE', 5000),

    'export_pdf_shard_size' => (int) env('EXPORT_PDF_SHARD_SIZE', 1000),

    'import_shard_size' => (int) env('IMPORT_SHARD_SIZE', 5000),

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
