# Services & stack

> The Docker services, ports, and storage wiring that make up the running app.

## Purpose

Describe what runs where, so you know which container to look in and how files/queues/data
flow.

## Key files

- `docker-compose.yml` — service definitions.
- `docker/app/Dockerfile` — the PHP image (incl. the `mysqldump`/`mysql` → `mariadb-*`
  symlinks backups need).
- `Makefile` — every workflow target (`make help`).
- `config/database.php`, `config/filesystems.php`, `config/keen.php`.
- `app/Support/helpers.php` — `dated_path()`.

## How it works

Services (`docker-compose.yml`):

| Service          | Purpose                                                    |
| ---------------- | ---------------------------------------------------------- |
| `app`            | PHP 8.4-FPM running Laravel                                |
| `nginx`          | Serves `public/`, proxies PHP to `app:9000`                |
| `node`           | Vite dev server — **dev profile only** (`make dev`)        |
| `queue`          | `queue:work --tries=3 --timeout=300` (database queue)      |
| `scheduler`      | `schedule:work`                                            |
| `mariadb`        | MariaDB 10.11 — DB sessions + queue + cache                |
| `phpmyadmin`     | DB admin UI                                                |
| `mailpit`        | local SMTP sink + web inbox (dev email)                    |
| `seaweedfs`      | S3-compatible object storage                               |
| `seaweedfs-init` | one-shot sidecar that creates the S3 buckets               |

Ports: app `8080`, phpMyAdmin `8081`, Mailpit inbox `8025`, Vite `5173`, SeaweedFS filer
`8888`, S3 API `8333`.

**Mail.** The transport is environment-driven, mirroring storage: `MAIL_MAILER` switches
`log` ↔ `smtp` the way `*_DISK_DRIVER` switches `local` ↔ `s3`, with **Mailpit** as the
always-on SMTP backend (the analogue of SeaweedFS). Dev defaults to `smtp` → Mailpit
(`MAIL_HOST=mailpit`, `MAIL_PORT=1025`; inbox at `:8025`); `log` writes to `storage/logs`.
This is a **two-layer** model: env is the base, and the Settings → Email tab
(`applyEmailSettings()`) overrides SMTP host/port/credentials/from **only when filled in**
(the seeded `smtp_host` is empty, so the override is dormant until an admin configures real
SMTP for prod). Mailpit's SMTP listens on `1025` internally on `appnet` — no host mapping.

**Storage disks.** `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE` all use the database.
Four non-public disks — `backups`, `exports`, `imports`, `uploads` — **default to `s3` (SeaweedFS)
in `.env.example`** (`*_DISK_DRIVER=s3`); the generic `FILESYSTEM_DISK` stays `local`. Flip any disk
back to `local` via its `*_DISK_DRIVER` env var, where it lives under `storage/app/private/*`
(outside the web root). Generated artifacts are nested under
`YYYY/MM/` via the `dated_path()` helper — reuse it for any new disk writes. **Downloads
always stream through gated controller actions, never a public URL.**

**Heavy work is queued** with DB-status tracking (backups, exports, imports): jobs flip a
`*Status` enum, capture failures into `error_message`, and notify on completion. Work below
`config('keen.*_sync_threshold')` runs synchronously.

## Decisions & why

- [ADR 0001 — Docker-only workflow](../decisions/0001-docker-only-workflow.md).

## Gotchas

- `node` is a one-off (`docker compose run --rm node`), not a long-running service — scripts
  and the pre-commit hook must invoke it that way, not via `exec`.
- Backups need `mysqldump`/`mysql`; the image symlinks them to MariaDB's binaries and the DB
  connection sets `dump.skip_ssl` + `dump.exclude_tables=['backups']` so a restore never
  wipes the backup list.
- `make clean` / `make refresh` delete generated files + storage runtime caches via a root
  container (because the node container writes root-owned files).

## Related

- [CI & hooks](ci-and-hooks.md)
- [Backups, exports & imports](../features/backups-exports-imports.md)
- [Files & media](../features/files-and-media.md)
- `CLAUDE.md` § "Stack" / "Files & images"
