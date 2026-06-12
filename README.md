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
| Mailpit (email inbox)          | http://localhost:8025    |
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
- **Settings** — typed spatie/laravel-settings groups (System/Email/Image)
  with typed forms; **Themes** — light/dark token palettes (with a color picker) applied live.
- **Files** — multi-file uploads (images + pdf/doc/docx/csv/xls/xlsx) via medialibrary on a
  private disk, gated download/preview, in-app viewer; plus self-service **My Documents**.
- **IP Lists** — whitelist/blacklist enforced by middleware (`whitelist_ip_only`).
- **Notifications** (bell + list), **Sessions** (revoke), **Audit Logs**, **Queue** monitor
  (retry/clear).
- **Backups** (spatie/laravel-backup, with captured failure reasons + restore), **My Exports**
  (csv/xls/xlsx/pdf), **My Imports** (upload→preview→process) — all via queued jobs with status
  tracking + gated downloads. Backups also run **unattended** on the scheduler (nightly create,
  weekly prune, daily staleness alert).

Cross-cutting conventions: every domain table carries an audit footer
(`record_status`/`created_by`/`updated_by`); lists use **keyset (cursor) pagination**;
`record_status` is a business Active/Inactive toggle (no soft deletes); light/dark theming
via CSS variables + a `data-theme` attribute.

## Architecture

| Service          | Purpose                                                |
| ---------------- | ------------------------------------------------------ |
| `app`            | PHP 8.4-FPM running the Laravel app                    |
| `caddy`          | Serves `public/`, proxies PHP to `app:9000`            |
| `node`           | Vite dev server (dev profile only — `make dev`)        |
| `queue`          | `queue:work --tries=3 --timeout=300` (database queue)  |
| `scheduler`      | `schedule:work`                                        |
| `mariadb`        | MariaDB 10.11 (database sessions + queue + cache)      |
| `phpmyadmin`     | DB admin UI                                            |
| `mailpit`        | Local SMTP sink + web inbox (dev email)                |
| `seaweedfs`      | S3-compatible object storage                           |
| `seaweedfs-init` | One-shot sidecar that creates the S3 buckets           |

**Debugging:** [Laravel Telescope](https://laravel.com/docs/telescope) is installed dev-only and
registered only in `APP_ENV=local`. Browse to **http://localhost:8080/telescope** to inspect
requests (with timing), DB queries, logs, jobs, mail, and exceptions. Outside local it's gated to the
`developer` role.

### Storage disks

`SESSION_DRIVER`, `QUEUE_CONNECTION`, and `CACHE_STORE` all use the database. Four
non-public disks — `backups`, `exports`, `imports`, `uploads` — **default to `s3` (SeaweedFS) in
`.env.example`** (the generic `FILESYSTEM_DISK` stays `local`); each switches back to `local` via its
`*_DISK_DRIVER` env var. On `local` they live under `storage/app/private/*`, outside the web root.
All generated artifacts are nested under a
`YYYY/MM/` folder (via the `dated_path()` helper). Downloads always go through gated controller
actions, never a public URL.

### Email

Mail transport is environment-driven the same way disks are: `MAIL_MAILER` switches `log` ↔
`smtp` like `*_DISK_DRIVER` switches `local` ↔ `s3`. Dev defaults to `smtp` → **Mailpit**, so
all app mail (password resets, export/import notices) lands in the inbox at
http://localhost:8025; set `MAIL_MAILER=log` to write to `storage/logs` instead. When `smtp`
is active, the **Settings → Email** tab overrides host/credentials/from — but only once filled
in (env is the base; the tab is the production override). Configure the tab and set
`MAIL_MAILER=smtp` in prod to point at a real SMTP server.

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
make fix        Auto-format + lint-fix PHP and frontend (writes files)
make is-mergeable  Run the full CI gate locally (check-only, no writes)
make hooks      Install the git pre-commit hook (Pint + Prettier + ESLint, check-only)
make logs       Follow logs
make ps         Container status
make tinker     Tinker REPL
make ide-helper Generate IDE helper files
```

## Code quality

- **Pint** (`pint.json`) — Laravel preset.
- **Larastan** (`phpstan.neon`, level 5) with a baseline in `phpstan-baseline.neon`.
- **ESLint + Prettier** — 4-space, single quotes, organized imports.
- **Pre-commit hook** in `.githooks/` — enable once per clone with `make hooks`. It runs
  Pint + Prettier + ESLint (all check-only) before each commit so a malformed edit can't
  reach CI; bypass a single commit with `SKIP_HOOKS=1 git commit …`.
- **CI** (`.github/workflows/ci.yml`) runs the backend (Pint, Larastan, Pest) and frontend
  (Prettier, ESLint, build) checks on every push/PR. Mirror it locally before pushing with
  `make is-mergeable` (check-only, no writes — the same gate CI enforces). To auto-fix style
  issues first, run `make fix` (Pint + ESLint --fix + Prettier --write), then `make is-mergeable`.

## Documentation

Deeper docs live in [`docs/`](docs/README.md), organized as a "memory palace" — one
canonical, same-shaped doc per topic (`decisions/`, `conventions/`, `infrastructure/`,
`features/`). The split: this **README is the front door** (what/why + quickstart, links
in), **`docs/` is the canonical long-form**, and **`CLAUDE.md` is the agent quick-ref**
that cross-links into `docs/`. Start at [`docs/README.md`](docs/README.md) for the map.

## Theming

Light/dark themes are CSS-variable based and toggled via a `data-theme` attribute on
`<html>` (see `resources/js/Components/ThemeProvider.tsx` and `resources/css/app.css`). A
no-flash inline script in `resources/views/app.blade.php` applies the persisted theme before
first paint.
