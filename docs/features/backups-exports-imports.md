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
- `app/Http/Controllers/{BackupController,ExportController,ImportController}.php` — the resources.
- `app/Support/RestoreSentinel.php` — the file-based "restore in progress" flag.
- `app/Http/Middleware/EnforceRestoreMode.php` — serves 503 during a restore.
- `config/database.php` — backup dump config (`dump.skip_ssl`, `dump.exclude_tables`).
- `app/Support/helpers.php` `dated_path()` — nests artifacts under `YYYY/MM/`.

## How it works

- **Status tracking.** Each job flips a `*Status` enum (Pending → Processing → Completed/Failed) and
  writes any exception into the `error_message` column, which the grid surfaces. Completion fires a
  notification.
- **Sync vs queue.** Exports/imports below `config('keen.*_sync_threshold')` run synchronously
  (instant download); above it they queue.
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
