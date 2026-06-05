# Backups, exports & imports

> Queued heavy work with DB-status tracking: backups, exports, imports.

> **TODO** — stub. Fill in when next touching this area, following [`TEMPLATE.md`](../TEMPLATE.md).

## Purpose

_TODO._

## Key files

- `app/Jobs/{CreateBackupJob,RestoreBackupJob,...}` — queued workers.
- `app/Http/Controllers/{BackupController,ExportController,ImportController}.php`.
- `config/database.php` — backup dump config (`dump.skip_ssl`, `dump.exclude_tables`).

## How it works

_TODO — `*Status` enums, `error_message` capture surfaced in the grid, completion
notifications, sync-vs-queue `config('keen.*_sync_threshold')`, `YYYY/MM/` archive paths._

## Decisions & why

_TODO — restore extracts to a temp workdir; dumps exclude the `backups` table so a restore
never wipes the backup list._

## Gotchas

_TODO — backups need `mysqldump`/`mysql` (symlinked to MariaDB binaries in the image)._

## Related

- [Services & stack](../infrastructure/services-and-stack.md)
- `CLAUDE.md` § "Heavy work is queued"
