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
    | Export sync threshold
    |--------------------------------------------------------------------------
    |
    | Exports at or below the threshold run synchronously (immediate download);
    | larger ones queue (RUNNING→DONE) and notify the owner on completion. The
    | count comes cheaply from SQL. Imports always queue — counting an upload
    | would mean parsing the whole file in the request, which we avoid.
    |
    */

    'export_sync_threshold' => (int) env('EXPORT_SYNC_THRESHOLD', 100),

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

    /*
    |--------------------------------------------------------------------------
    | Video upload allowlist
    |--------------------------------------------------------------------------
    |
    | Video extensions the Files admin uploader (/files/create) accepts on top of
    | the image (keen.image_extensions) and document (keen.file_document_extensions)
    | types. Large videos take the resumable chunked path (InitUploadRequest);
    | mp4/webm/ogv play inline in <FileViewer>, the rest fall back to download.
    |
    */

    'video_extensions' => ['mp4', 'webm', 'ogv', 'mov', 'm4v', 'avi', 'mkv'],

    /*
    |--------------------------------------------------------------------------
    | Backup retention window
    |--------------------------------------------------------------------------
    |
    | The weekly `backups:prune` command deletes Generated/Failed backups (rows +
    | archives) older than this many days, always keeping the most recent
    | generated backup. spatie's backup:clean is unusable here because
    | CreateBackupJob relocates archives out of the folder spatie scans.
    |
    */

    'backup_keep_days' => (int) env('BACKUP_KEEP_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Backup staleness alert
    |--------------------------------------------------------------------------
    |
    | The daily `backups:monitor` command alerts developers (in-app) when no
    | Generated backup has completed within this many hours — a custom health
    | check, since spatie's backup:monitor can't see our relocated archives.
    |
    */

    'backup_alert_after_hours' => (int) env('BACKUP_ALERT_AFTER_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Resumable uploads
    |--------------------------------------------------------------------------
    |
    | The Google-Drive-style chunked uploader (FileDropzone `resumable` mode →
    | the /uploads endpoints). `upload_chunk_size` is the baseline chunk; it
    | scales up for very large files to keep the S3 part count under 10,000 and
    | is capped at 32 MB so each chunk request stays well under the 64 MB
    | PHP/Caddy body limit (and above S3's 5 MiB minimum part size).
    | `max_upload_size` bounds the declared total. Sessions abandoned for
    | `upload_session_ttl_hours` are aborted by `uploads:prune`. On the local
    | driver, assembling a file larger than the async threshold is queued
    | (FinalizeUploadJob) instead of done inline; S3 always completes inline.
    |
    */

    'upload_chunk_size' => (int) env('UPLOAD_CHUNK_SIZE', 8 * 1024 * 1024),

    'max_upload_size' => (int) env('MAX_UPLOAD_SIZE', 5 * 1024 * 1024 * 1024),

    'upload_session_ttl_hours' => (int) env('UPLOAD_SESSION_TTL_HOURS', 24),

    'upload_local_concat_async_threshold' => (int) env('UPLOAD_LOCAL_CONCAT_ASYNC_THRESHOLD', 100 * 1024 * 1024),

];
