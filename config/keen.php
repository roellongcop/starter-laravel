<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain table prefix
    |--------------------------------------------------------------------------
    |
    | Applied by App\Models\BaseModel to domain tables (e.g. tbl_widgets).
    | Framework/package tables (users, sessions, jobs, permission tables, …)
    | are NOT prefixed — a model opts out by declaring its own $table.
    |
    */

    'table_prefix' => env('DB_TABLE_PREFIX', 'tbl_'),

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

];
