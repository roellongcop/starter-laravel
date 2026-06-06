# Backups, exports & imports

> Queued heavy work with DB-status tracking: backups, exports, imports — plus the restore gate.

## Purpose

Long-running data operations run as queued jobs that record their progress in a status column, so the
UI can show a live grid and surface failures instead of blocking a request. A database *restore* is
the one destructive operation, so it additionally fences the whole app behind a maintenance gate for
everyone but the operator while it runs.

## Key files

- `app/Jobs/{CreateBackupJob,RestoreBackupJob}` + export/import jobs — queued workers that flip a
  `*Status` enum and capture failures into `error_message`.
- `app/Jobs/GenerateExportJob` — the export **sync/small** single-file path (imports always queue).
- `app/Jobs/{DispatchExportJob,ExportShardJob,FinalizeExportJob}` and
  `{DispatchImportJob,ImportShardJob,FinalizeImportJob}` — the **sharded** path for large jobs.
- `app/Http/Controllers/{BackupController,ExportController,ImportController}.php` — the resources.
- `app/Support/RestoreSentinel.php` — the file-based "restore in progress" flag.
- `app/Http/Middleware/EnforceRestoreMode.php` — serves 503 during a restore.
- `config/database.php` — backup dump config (`dump.skip_ssl`, `dump.exclude_tables`).
- `app/Support/helpers.php` `dated_path()` — nests artifacts under `YYYY/MM/`.

## How it works

- **Status tracking.** Each job flips a `*Status` enum (Pending → Processing → Completed/Failed) and
  writes any exception into the `error_message` column, which the grid surfaces. Completion fires a
  notification.
- **Sync vs queue.** Exports below `config('keen.export_sync_threshold')` run synchronously
  (instant download) via `GenerateExportJob`; above it they queue. **Imports always queue** via
  `DispatchImportJob` — the preview page only samples the first rows (`UsersPreview` + `WithLimit`)
  and the process action just enqueues, so the request never parses the whole upload (counting a
  100k-row file in-request was the bottleneck). The real total is tallied in the background.
- **Sharding (large jobs).** A queued export/import is split into shards of
  `config('keen.export_shard_size')` / `import_shard_size` rows (default 5000) and run as a
  `Bus::batch`. A coordinator (`Dispatch{Export,Import}Job`) builds the shards — exports by id-range
  windows, imports by row slices — each shard job handles one slice, and a `Finalize{Export,Import}Job`
  runs on batch completion. **Exports** write one file per shard and the finalizer zips them into a
  single `.zip` download (so an `.xls` shard never approaches the 65,536-row format cap). **PDF uses a
  smaller dedicated shard size** (`config('keen.export_pdf_shard_size')`, default 1000) because DomPDF
  renders the whole shard into memory at once rather than streaming it like the spreadsheet writers — a
  5k-row PDF shard runs long enough that the queue assumes the worker died and re-attempts it.
  **Imports** validate + `updateOrCreate` their slice (preserving password hashing)
  and write a per-shard error CSV; the finalizer concatenates them into one report. Shards bump
  `processed_rows`/`success`/`failed` atomically, and the Exports/Imports grids auto-poll
  (`useStatusPoll`) to show a live progress bar.
- **Round-trippable spreadsheets.** `csv/xls/xlsx` exports (`app/Exports/UsersExport.php`) emit the
  **real users-table column names** as headers — `id, name, email, username, user_status, roles,
  password, password_hint, created_at, updated_at` — so a downloaded file can be re-uploaded straight
  through the import (Excel's `slug` heading formatter keys rows by these). The import
  (`UsersImport::importRow`, used by `ImportShardJob`) upserts on `email`
  and consumes `name`/`email`/`username`/`user_status`/`password`/`password_hint`/`roles` (it also
  still accepts a legacy `status` header). `roles` is a comma-separated list of role names; the import
  `syncRoles()`s them so a round-trip restores access exactly — a row naming an **unknown role fails**
  and is reported, and a file **without** a `roles` column leaves existing roles untouched. The
  exported `password` is the **bcrypt hash**; re-importing preserves it because Laravel's `hashed`
  cast skips already-hashed values, so logins survive a round-trip. PDF stays a human-readable report
  (its own blade), not a round-trip format.
- **Queue `retry_after` invariant.** These jobs declare long `$timeout`s (`Finalize*Job` = 600s) and
  `$tries = 1` (fail loudly, no retry). The queue connection's `retry_after` (`config/queue.php`, default
  **700s**) must stay **larger than the longest job timeout** — otherwise a still-running job is treated
  as abandoned, released, and re-reserved, failing immediately with *"has been attempted too many
  times."* Raise both together if a job ever needs a longer timeout.
- **Paths.** Generated artifacts (backups/exports/imports) are nested under `YYYY/MM/` via
  `dated_path()`, matching uploads. `CreateBackupJob` relocates the spatie archive there.
- **Restore.** `RestoreBackupJob` extracts the `.sql` dump from the archive into a temp workdir
  (recursively cleaned afterwards) and imports it via the `mysql` client. Before importing it sets the
  `RestoreSentinel`; `EnforceRestoreMode` then returns **503** to everyone except the operator who
  triggered it (or a developer) and the auth routes — so the operator can re-authenticate after the
  session store is replaced.

## Decisions & why

- **`RestoreSentinel` is a file, not the cache.** The cache is database-backed and would itself be
  overwritten during a DB restore, so a file in `storage/app` is what reliably survives the restore
  window.
- **Restore is connection-scoped and destructive** — it overwrites only the configured app connection;
  the job is explicitly marked DESTRUCTIVE in code.
- **Dumps exclude the `backups` table** (`config/database.php` `dump.exclude_tables`) so restoring an
  older snapshot never wipes the list of available backups; `dump.skip_ssl` lets the dump connect
  without TLS.

## Gotchas

- Backups need the `mysqldump`/`mysql` binaries — the app image symlinks them to MariaDB's
  `mariadb-dump`/`mariadb` (`docker/app/Dockerfile`).
- The auth route names in `EnforceRestoreMode::$allowed` must stay in sync with the real auth routes,
  or an operator can lock themselves out mid-restore.

## Related

- [Services & stack](../infrastructure/services-and-stack.md) — disks, queue/scheduler.
- `CLAUDE.md` § "Heavy work is queued"
