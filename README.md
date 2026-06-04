# Laravel Keen Admin Starter

An admin-dashboard starter kit: **Laravel 12** + **Breeze (Inertia + React, TypeScript)** +
**Tailwind** + **shadcn/ui**, fully containerized and driven by a `Makefile`. No host PHP,
Node, or Composer required — everything runs in Docker.

## Requirements

- Docker + Docker Compose (v2)
- GNU Make

## Quick start

```bash
cp .env.example .env     # adjust ports if 8080/8081/3306 are taken on your host
make build               # build the app image
make setup               # up + composer/npm install + key + migrate --seed + build assets
```

Then open:

| Service      | URL                                |
| ------------ | ---------------------------------- |
| App          | http://localhost:8080              |
| phpMyAdmin   | http://localhost:8081              |
| Vite (dev)   | http://localhost:5173 (`make dev`) |
| SeaweedFS filer (browse files) | http://localhost:8888    |
| SeaweedFS S3 API               | http://localhost:8333    |

For active frontend development, run `make dev` (starts the Vite dev server with HMR)
alongside the stack.

## Demo logins

`make setup`/`make fresh` seed three accounts — **the password equals the email**:

| Email                       | Role         | Access                                  |
| --------------------------- | ------------ | --------------------------------------- |
| `developer@developer.com`   | developer    | Everything (god mode — bypasses gates)  |
| `superadmin@superadmin.com` | superadmin   | All resource permissions                |
| `admin@admin.com`           | admin        | Read-most + own content/data            |

## Features

A complete admin back-office, all gated by a declared permission registry
(`config/permissions.php`) via spatie/laravel-permission:

- **Dashboard** — permission-aware metric tiles, recent activity, and a global
  search palette (⌘K) across resources.
- **Users / UserMeta / Roles** — full CRUD; roles bundle permissions and derive the
  sidebar (`module_access`/`main_navigation`); inline per-user meta.
- **Settings** — typed spatie/laravel-settings groups (System/Email/Image/Notification)
  with typed forms; **Themes** — light/dark token palettes (with a color picker) applied live.
- **Files** — multi-file uploads (images + pdf/doc/docx/csv/xls/xlsx) via medialibrary on a
  private disk, gated download/preview, in-app viewer; plus self-service **My Documents**.
- **IP Lists** — whitelist/blacklist enforced by middleware (`whitelist_ip_only`).
- **Notifications** (bell + list), **Sessions** (revoke), **Audit Logs**, **Visitors /
  Visit Logs** (cookie tracking), **Queue** monitor (retry/clear).
- **Backups** (spatie/laravel-backup, with captured failure reasons + restore), **My Exports**
  (csv/xls/xlsx/pdf), **My Imports** (upload→preview→process) — all via queued jobs with status
  tracking + gated downloads.

Cross-cutting conventions: every domain table carries an audit footer
(`record_status`/`created_by`/`updated_by`); lists use **keyset (cursor) pagination**;
`record_status` is a business Active/Inactive toggle (no soft deletes); light/dark theming
via CSS variables + a `data-theme` attribute.

## Architecture

| Service          | Purpose                                                |
| ---------------- | ------------------------------------------------------ |
| `app`            | PHP 8.4-FPM running the Laravel app                    |
| `nginx`          | Serves `public/`, proxies PHP to `app:9000`            |
| `node`           | Vite dev server (dev profile only — `make dev`)        |
| `queue`          | `queue:work --tries=3 --timeout=300` (database queue)  |
| `scheduler`      | `schedule:work`                                        |
| `mariadb`        | MariaDB 10.11 (database sessions + queue + cache)      |
| `phpmyadmin`     | DB admin UI                                            |
| `seaweedfs`      | S3-compatible object storage                           |
| `seaweedfs-init` | One-shot sidecar that creates the S3 buckets           |

### Storage disks

`SESSION_DRIVER`, `QUEUE_CONNECTION`, and `CACHE_STORE` all use the database. Four
non-public disks — `backups`, `exports`, `imports`, `uploads` — live under
`storage/app/private/*` (outside the web root) and are each switchable between `local` and
`s3` (SeaweedFS) via their `*_DISK_DRIVER` env var. All generated artifacts are nested under a
`YYYY/MM/` folder (via the `dated_path()` helper). Downloads always go through gated controller
actions, never a public URL.

## Make targets

Run `make help` for the full list. The common ones:

```
make build      Build images
make up         Start the stack (waits for a healthy DB)
make down       Stop the stack
make setup      One-shot fresh start (install, key, migrate --seed, assets)
make fresh      migrate:fresh --seed
make refresh    Full wipe: down -v + clean + rebuild + setup
make clean      Delete generated/uploaded files + storage runtime caches
make shell      Bash in the app container
make dev        Vite dev server (HMR)
make assets     Build production assets
make test       Pest test suite
make pint       Format PHP (Pint)
make stan       Static analysis (Larastan)
make lint       ESLint + Prettier check
make is-mergeable  Run the full CI gate locally (check-only, no writes)
make logs       Follow logs
make ps         Container status
make tinker     Tinker REPL
make ide-helper Generate IDE helper files
```

## Code quality

- **Pint** (`pint.json`) — Laravel preset.
- **Larastan** (`phpstan.neon`, level 5) with a baseline in `phpstan-baseline.neon`.
- **ESLint + Prettier** — 4-space, single quotes, organized imports.
- **Pre-commit hook** in `.githooks/` — enable with `git config core.hooksPath .githooks`.
- **CI** (`.github/workflows/ci.yml`) runs the backend (Pint, Larastan, Pest) and frontend
  (Prettier, ESLint, build) checks on every push/PR. Mirror it locally before pushing with
  `make is-mergeable` (check-only, no writes — the same gate CI enforces).

## Theming

Light/dark themes are CSS-variable based and toggled via a `data-theme` attribute on
`<html>` (see `resources/js/Components/ThemeProvider.tsx` and `resources/css/app.css`). A
no-flash inline script in `resources/views/app.blade.php` applies the persisted theme before
first paint.
