# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Everything runs in Docker — never use host PHP/Node/Composer

There is no host PHP, Node, or Composer. All work happens in containers, wrapped by a
`Makefile` of one-word targets. `make help` lists them. Key ones:

```bash
make build      # build the app image
make setup      # up + composer/npm install + key + migrate --seed + build assets (first run)
make up / down  # start / stop the stack (up waits for a healthy DB)
make dev        # Vite dev server with HMR (http://localhost:5173); app is http://localhost:8080
make fresh      # DB only: migrate:fresh --seed
make refresh    # full wipe: down -v + clean (local files + storage/runtime caches) + rebuild + setup
make clean      # delete generated/uploaded files + storage caches (logs, framework, bootstrap)
make test       # Pest suite (in-container)
make pint       # PHP formatter (Laravel Pint)
make stan       # Larastan (phpstan) static analysis
make lint       # eslint --fix + prettier --check on resources/js
make fix        # auto-fix everything (write): pint + eslint --fix + prettier --write
make is-mergeable # the full CI gate locally, check-only: pint --test + stan + test + prettier/eslint check + build
make hooks      # install the pre-commit hook (Pint + Prettier + ESLint, check-only)
make shell      # bash in the app container; make tinker; make ide-helper
```

Run `make hooks` once per clone to enable the `.githooks/pre-commit` gate (it sets
`core.hooksPath`). It runs the same frontend/Pint checks as CI before each commit so a
malformed edit can't reach the pipeline; bypass a single commit with `SKIP_HOOKS=1`.

Under the hood: `APP := docker compose exec -T app`, `NODE_RUN := docker compose run --rm node`.

**Running a subset of tests** (Pest, no Make target):
```bash
docker compose exec -T app php artisan test --filter='it lists users'
docker compose exec -T app php artisan test tests/Feature/Roles/MenuBuilderTest.php
```

**Frontend type-check / build / lint without Make:**
```bash
docker compose run --rm node npx tsc --noEmit
docker compose run --rm node npx eslint resources/js --ext .ts,.tsx --fix
docker compose run --rm node npm run build
```

Tests use Pest on an **in-memory sqlite** DB with `QUEUE_CONNECTION=sync`, array cache/session/mail
(`phpunit.xml`). `tests/Pest.php` binds the Laravel `TestCase` + `RefreshDatabase` only `->in('Feature')`,
so **all real tests live in `tests/Feature/`** (HTTP/Inertia/DB); `tests/Unit/` is plain PHPUnit. Most
feature tests `seed(PermissionSeeder::class, RoleSeeder::class)` and use the `actingAsRole('developer')`
helper. `tests/Feature/SmokeTest.php` GETs every Inertia page route asserting 200 — add new pages there.

Demo logins (seeded by `make setup`/`make fresh`): `developer@developer.com`,
`superadmin@superadmin.com`, `admin@admin.com` — **password equals the email**.

## Stack

Laravel 12 + Inertia + React + TypeScript + Tailwind + shadcn/ui. Services (see
`docker-compose.yml`): `app` (PHP 8.4-FPM), `nginx`, `node` (dev profile only), `queue`,
`scheduler`, `mariadb` (database queue/session/cache), `phpmyadmin`, `seaweedfs` (+ a
one-shot `seaweedfs-init` that creates the S3 buckets).

## Architecture & cross-cutting conventions

These rules are implemented once and obeyed everywhere — understand them before adding a resource.

**Domain models** (`app/Models/*`) extend `BaseModel`, which composes `IsResource` +
`HasRecordStatus` + `Blameable` + owen-it auditing. `User` can't extend `BaseModel` (it's
`Authenticatable`) so it uses the same traits directly. Tables are **standard plural snake_case,
no prefix** (a model sets `$table` only to override, e.g. `UserMeta` → `user_meta`).

**`record_status` is a business Active/Inactive toggle, NOT soft deletes.** `HasRecordStatus`
(`app/Models/Concerns/`) adds a global `active` scope (hides inactive by default), plus
`withInactive()` / `onlyInactive()` / `activate()` / `inactivate()` and a static `bulkAction()`.
Critically, it overrides `resolveRouteBindingQuery()` to **drop the active scope for route binding**,
so admins can show/edit/delete inactive rows (otherwise `/users/{inactive}/edit` 404s). Listing
inactive rows is gated by the `view-inactive` permission.

**Keyset (cursor) pagination only — no page numbers.** Controllers do
`Model::query()->...->keyset()->cursorPaginate(config('keen.pagination_size'))` then wrap with the
global `cursorResponse()` helper (`app/Support/helpers.php`) → `{data, next_cursor, prev_cursor,
has_more, total?}`, consumed by `<CursorPager>`. Pass a 3rd arg to `cursorResponse()` to include an
exact `total`.

**Authorization** = spatie/laravel-permission driven by a **declared registry**
(`config/permissions.php`), synced via the `permissions:sync` command (run by `PermissionSeeder`, not
per request). Abilities are `"{resource}.{ability}"` (e.g. `users.update`) plus standalone
`view-inactive`. `Gate::before` gives the `developer` role god-mode. Controllers authorize per method;
the frontend gates UI with `<Can ability="...">` (fed by `auth.modules`/`auth.permissions` shared via
`HandleInertiaRequests`).

**Sidebar navigation** is roles-aware: `App\Support\Navigation` derives `module_access` (button
visibility) and a sidebar tree from a role's permissions, but a role may also store a custom
`main_navigation` (drag-and-drop menu builder on the role form). `Navigation::forUser()` merges all of
a user's roles' menus by `priority` and intersects with their permissions, so the menu can never show
something the user can't access. Icons are string names resolved via `resources/js/lib/navIcons.tsx`.

**Files & images** flow through one path: `app/Actions/StoreUploadedFile` creates a `File`
(medialibrary) on the private `uploads` disk; `App\Support\MediaPathGenerator` stores at
`YYYY/MM/<random>.ext`. On-demand resizing is league/glide via `App\Support\ImageStreamer` (cached on a
local `image-cache` disk; widths clamp to a preset ladder in `App\Support\ImageParams`, so
`?w=`/`?h=`/`fit=` are the cache key). Reusable frontend pieces: `<ImagePicker>` (crop/upload/camera →
`/media`), `<FileDropzone>` (generic upload → returns an id), `<FileViewer>` (image/pdf native,
csv/xls/xlsx via SheetJS, docx via mammoth — both lazy-loaded), `<Avatar>` (requests a preset size).
Storage disks `uploads`/`exports`/`imports`/`backups` **default to `s3` (SeaweedFS) in `.env.example`**
via `*_DISK_DRIVER` (the generic `FILESYSTEM_DISK` stays `local`); flip any back with its driver var.
**Downloads are always streamed through gated controller actions, never public URLs**.
Generated artifacts (backups/exports/imports) are nested under `YYYY/MM/` like uploads via the
`dated_path()` helper (`app/Support/helpers.php`) — reuse it for any new disk writes.

**Heavy work is queued** with DB-status tracking: backups (spatie/laravel-backup), exports
(maatwebsite/excel + dompdf), imports — jobs flip a `*Status` enum, capture failures into an
`error_message` column (surfaced in the grid), and notify on completion. Export/import below
`config('keen.*_sync_threshold')` run synchronously, above it queue. **Backups** need the
`mysqldump`/`mysql` binaries — the app image symlinks them to MariaDB's `mariadb-dump`/`mariadb`
(`docker/app/Dockerfile`), and the DB connection sets `dump.skip_ssl` + `dump.exclude_tables=['backups']`
(`config/database.php`) so dumps connect without TLS and a restore never wipes the backup list.
`CreateBackupJob` relocates the archive under `YYYY/MM/`; `RestoreBackupJob` extracts to a temp
workdir it recursively cleans.

**User feedback**: controllers `->with('success'|'error', …)`; `HandleInertiaRequests` shares `flash`
as an `Inertia::always()` prop so partial reloads (`router.reload({ only: [...] })`) re-evaluate the
one-shot bag to null instead of re-toasting it; `app.tsx` turns it into toasts globally
(`resources/js/hooks/use-toast.ts` + `<Toaster>`). Axios uploads (no session flash) toast client-side.

## Conventions when editing

- **Backend:** match existing controller→FormRequest→Policy→Inertia-page shape; resource lists follow
  the keyset+`cursorResponse` pattern; bulk/`destroy` redirect with `back()` to preserve filters.
  Models with any `@property` need a complete set (incl. `created_at`/`updated_at`) or Larastan fails;
  `checkModelProperties` is off; avoid generic `Attribute<...>` return docblocks (covariance error).
- **Frontend:** React is all TypeScript; shadcn primitives live in `resources/js/Components/ui/`.
  Prettier (`prettier-plugin-organize-imports` + `prettier-plugin-tailwindcss`) enforces import
  ordering and class sorting — run `eslint --fix` after edits (manually-ordered imports get reshuffled). `<BackButton fallback>` navigates to the previous
  page with a **fresh** `router.get` via a per-tab sessionStorage nav stack (`lib/navHistory.ts`), not
  `history.back()` (which serves Inertia's stale cache).
- **The `node` container runs as root**, so `npm run build` writes root-owned files under
  `public/build`. `make clean` deletes generated/uploaded files **and** storage runtime caches
  (`storage/framework/*`, `storage/logs`, `bootstrap/cache`, backup-temp/restore workdirs) via a root
  container for this reason; `make refresh` runs it as part of a full wipe. Logs use the `daily` channel
  (`LOG_STACK=daily`) so they rotate instead of growing unbounded.
- After changing controllers/config, a cached config can mask it: `make shell` →
  `php artisan config:clear`.

## Workflow

Build phase-by-phase; after a feature, run `make test` + `make pint` + `make stan` + `make lint` +
`tsc`/build before considering it done — or just `make is-mergeable`, which runs that whole gate
(check-only) exactly as CI (`.github/workflows/ci.yml`) does. The full implementation history lives in
the auto-memory at `~/.claude/projects/.../memory/`.

**Deeper docs:** long-form rationale and per-feature deep dives live in `docs/` (start at
`docs/README.md`) — ADRs under `docs/decisions/`, code style in `docs/conventions/`,
infrastructure in `docs/infrastructure/`, per-feature in `docs/features/`. This file stays the
dense quick-ref; `docs/` is the canonical long-form. Keep facts in one place and cross-link.
