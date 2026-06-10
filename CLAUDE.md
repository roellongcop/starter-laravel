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
`scheduler`, `postgres` (database queue/session/cache), `adminer` (web DB UI), `seaweedfs` (+ a
one-shot `seaweedfs-init` that creates the S3 buckets).

**Debugging:** Laravel Telescope (dev-only, local only, developer-gated) at `/telescope` for
requests/queries/logs — see `docs/infrastructure/services-and-stack.md` § "Debugging — Telescope".

**Observability (prod):** structured JSON logs carrying a `request_id` (the `AssignRequestId`
middleware via `Context`, propagated into jobs) + the Laravel Pulse dashboard at `/pulse`
(developer-gated). See `docs/infrastructure/observability.md`.

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
`pg_dump`/`psql` binaries — the app image installs `postgresql-client`
(`docker/app/Dockerfile`), and the DB connection sets `dump.exclude_tables=['backups', …]`
(`config/database.php`) so a restore never wipes the backup list.
`CreateBackupJob` relocates the archive under `YYYY/MM/`; `RestoreBackupJob` extracts to a temp
workdir it recursively cleans and imports via `psql`.

**Scheduled tasks** live in `routes/console.php`, run by the `scheduler` service. Backups are
automated by `backups:run`/`backups:prune`/`backups:monitor` (nightly/weekly/daily) operating over
the `backups` **table** — spatie's `backup:clean`/`backup:monitor` are **not** used (they scan the
folder archives are relocated out of). Run/test any via `make backup`/`backup-prune`/`backup-monitor`.
Deep dive: `docs/features/backups-exports-imports.md`.

**Deploying.** On a Docker VPS the `queue`/`scheduler` services run as-is — layer
`docker-compose.prod.yml` on top (`-f docker-compose.yml -f docker-compose.prod.yml`) to harden
config + lock down ports. Runbook: `docs/infrastructure/deployment.md`.

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

> **Execution environment (project override).** This project has NO host PHP/Node/Composer — everything runs in Docker. Any bare `php artisan …`, `composer …`, `npm …`, or `vendor/bin/…` command below must run in-container: `docker compose exec -T app php artisan …`, `docker compose run --rm node npm …`, etc. Prefer the `make` targets (`make test`, `make pint`, `make stan`, `make tinker`, `make dev`) — see the top of this file. Where these guidelines conflict with the project conventions above, the project conventions win.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- tightenco/ziggy (ZIGGY) - v2
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- laravel/telescope (TELESCOPE) - v5
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/react (INERTIA_REACT) - v2
- eslint (ESLINT) - v8
- prettier (PRETTIER) - v3
- react (REACT) - v18
- tailwindcss (TAILWINDCSS) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, they likely need assets rebuilt: `docker compose run --rm node npm run build`, or the HMR dev server via `make dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands in-container: `docker compose exec -T app php artisan route:list`. Use `php artisan list` to discover commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `docker compose exec -T app php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `docker compose exec -T app php artisan config:show app.name`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code (interactive REPL via `make tinker`). Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `docker compose exec -T app php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `docker compose exec -T app php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `docker compose exec -T app php artisan test` with a specific filename or `--filter`.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `docker compose exec -T app php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). List commands with `php artisan list` and check parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `docker compose exec -T app php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, run `docker compose run --rm node npm run build`, or ask the user to start HMR with `make dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `docker compose exec -T app vendor/bin/pint --dirty` before finalizing changes (or `make pint` to format everything) to match the project's expected style.
- Do not run pint in `--test` mode to fix style; run `docker compose exec -T app vendor/bin/pint --dirty` (or `make pint`) to actually apply the fixes.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `docker compose exec -T app php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `make:test --pest SomeFeatureTest` instead of `make:test --pest Feature/SomeFeatureTest`.
- Run the full suite with `make test`; run a subset with `docker compose exec -T app php artisan test --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
