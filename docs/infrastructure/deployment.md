# Deployment

> Taking the stack to production — the easy Docker-VPS path, and the manual cPanel
> shared-hosting path where you wire up by hand what containers gave you for free.

## Purpose

The repo is built for local Docker development. Production has two very different shapes:

- **A Docker-capable VPS** — the `docker-compose.yml` stack already contains `queue` and
  `scheduler` services, so most of the work is *already done*. You harden config, add TLS,
  and lock down ports.
- **Shared cPanel hosting** — there is **no Docker**. Every container becomes a native
  equivalent you set up by hand: SeaweedFS → `local` disks, the `queue` worker → a cron, the
  `scheduler` → a cron, Mailpit → real SMTP, nginx → Apache.

This room is the runbook for both.

## Key files

- `docker-compose.prod.yml` — VPS override (restart policy, locked-down ports).
- `.env.cpanel.example` — a fill-in-the-blanks `.env` for shared hosting.
- `public/.htaccess` — the Apache front-controller rewrite (ships in-repo; nothing to add).
- `.env.example` — the dev baseline; lines 22-25 carry the prod JSON-logging block.
- `routes/console.php` — the scheduled commands a cron must drive in production.
- `config/filesystems.php` — the `*_DISK_DRIVER` `s3`↔`local` switches.
- `config/database.php` — the backup `dump.skip_ssl` / `exclude_tables` settings.

## How it works

### Production environment, both paths

Whatever the host, the production `.env` differs from dev in the same ways:

| Variable | Dev | Production |
| --- | --- | --- |
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | **`false`** (debug leaks stack traces) |
| `APP_URL` | `http://localhost:8080` | `https://your-domain.com` |
| `APP_KEY` | seeded | **freshly generated** (`php artisan key:generate`) |
| `LOG_LEVEL` | `debug` | `warning` |
| `SESSION_SECURE_COOKIE` | unset | `true` |

The app reads `APP_URL`'s scheme and calls `URL::forceScheme('https')` when it's `https://`
(`app/Providers/AppServiceProvider.php`), so links/assets are correct behind a TLS-terminating
proxy. For 12-factor logs, the prod block already documented in `.env.example` switches to
machine-parseable JSON on stdout carrying the `request_id` (see [Observability](observability.md)):

```dotenv
LOG_CHANNEL=stderr
LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter
```

Always finish a deploy with `php artisan optimize` (config/route/view cache) and run
`php artisan optimize:clear` before each redeploy or `.env` change.

> **Seeding in production.** Run only the structural seeders — `PermissionSeeder`,
> `RoleSeeder`, `ThemeSeeder` — with `--force`. **Do not** run the full `db:seed` /
> `UserSeeder`: it creates demo logins whose *password equals the email* plus 25 fake users.
> Create your real admin yourself (e.g. `make tinker` → `User::create([...])->assignRole('superadmin')`).

### Path A — Docker VPS (self-contained)

The base stack already runs `app`, `nginx`, `queue` (`queue:work`), `scheduler`
(`schedule:work`), `ssr` (the Inertia SSR renderer), `mariadb`, and `seaweedfs` (+ the one-shot
`seaweedfs-init`). So the queue and scheduler "just work" — no cron needed. Layer the production
override on top:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

`docker-compose.prod.yml` flips `restart: always`, stops publishing the MariaDB / SeaweedFS /
phpMyAdmin ports to the host (the app reaches them on the internal `appnet`), and parks
phpMyAdmin behind a `debug` profile. Only nginx (`:80`) is exposed.

First-run, inside the app container (`docker compose exec app …`):

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=ThemeSeeder --force
php artisan storage:link
php artisan optimize
```

Build assets with `make assets` (or `docker compose run --rm node npm run build`) so
`public/build` **and** the SSR bundle `bootstrap/ssr/ssr.js` exist (`npm run build` now runs
`vite build && vite build --ssr`).

**Server-side rendering (public/SEO pages).** The public marketing pages (`/`, `/contact`, and
any future DB-driven SEO pages — the routes behind the `EnableSsr` middleware) are server-side
rendered for crawlers and social cards; the authenticated admin app stays plain CSR. The `ssr`
service runs `node bootstrap/ssr/ssr.js` on `:13714`; the app dispatches to it via
`INERTIA_SSR_URL=http://ssr:13714`. SSR is opt-in per request (`inertia.ssr.enabled` defaults
false, flipped on by `EnableSsr`), and if the bundle is missing or `ssr` is down the app falls
back to CSR (`inertia.ssr.ensure_bundle_exists`). Verify after deploy with
`docker compose exec app php artisan inertia:check-ssr`. Rebuild the bundle on every deploy so
`ssr` serves current code, and restart `ssr` alongside `app`.

**TLS.** The stack's nginx speaks plain `:80` only — terminate HTTPS at a reverse proxy in
front of it (Caddy/Traefik, or host-nginx + certbot). Bind the app port to localhost so only
the proxy reaches it (`APP_PORT=127.0.0.1:8080`). Minimal Caddy example:

```caddy
your-domain.com {
    reverse_proxy 127.0.0.1:8080
}
```

**Persistence & backups.** All state lives in the named volumes `mariadb-data` and
`seaweedfs-data`. The in-app nightly `backups:run` covers the **database**; the SeaweedFS
object store (uploads/exports/imports) is *not* in that dump — snapshot the `seaweedfs-data`
volume separately. See [Backups, exports & imports](../features/backups-exports-imports.md).

**Updates.** `git pull` on the host → `composer install --no-dev --optimize-autoloader` →
`npm run build` (rebuilds the client **and** SSR bundles) → `php artisan migrate --force` →
`php artisan optimize:clear && php artisan optimize` → `docker compose … restart app queue
scheduler ssr`.

**Optional — managed services.** To offload state: point `DB_HOST` at a managed database, set
`*_DISK_DRIVER=s3` with real `AWS_*` credentials/endpoint, and drop the `mariadb` / `seaweedfs`
/ `seaweedfs-init` services from the override.

### Path B — Shared cPanel (manual wiring)

No Docker, so each container maps to a cPanel-native equivalent:

| Container (dev) | cPanel equivalent |
| --- | --- |
| `app` (PHP-FPM) | cPanel PHP 8.4 (MultiPHP Manager) |
| `nginx` | Apache + the shipped `public/.htaccess` |
| `mariadb` | a cPanel MySQL®/MariaDB database + user |
| `node` | build assets locally/CI, upload `public/build` |
| `seaweedfs` | `*_DISK_DRIVER=local` → `storage/app/private/*` |
| `queue` | a cron-driven `queue:work` (see below) |
| `scheduler` | one cPanel cron → `schedule:run` |
| `mailpit` | real SMTP (cPanel mail account or external) |
| `phpmyadmin` | cPanel's built-in phpMyAdmin |

Steps:

1. **PHP version** — set **8.4** in *MultiPHP Manager*; in *Select PHP Version* enable
   `pdo_mysql`, `gd`, `zip`, `exif`, `bcmath`, `intl`, `mbstring`, `openssl`, `fileinfo`.
2. **Upload code** into a non-public app root, e.g. `~/laravel` (cPanel *Git Version Control*
   or an uploaded zip). Do **not** put the whole project in `public_html`.
3. **Document root → `public/`.** Point the domain's docroot at `~/laravel/public` (cPanel
   *Domains → manage*). The shipped `public/.htaccess` handles all routing — no extra Apache
   config. *Fallback* for hosts that lock the docroot to `public_html`: move the contents of
   `public/` into `public_html/` and edit the two `require`/`__DIR__` paths in
   `public_html/index.php` to point back at `~/laravel`.
4. **Dependencies** — `composer install --no-dev --optimize-autoloader` over SSH / cPanel
   *Terminal*. No shell? Use cPanel's Composer UI, or build `vendor/` locally and upload it.
5. **Assets** — build locally (`make assets` / `npm run build`) and upload `public/build/`.
   Node is unreliable on shared hosting, and a missing manifest makes Vite throw at runtime.
6. **`.env`** — copy `.env.cpanel.example` → `.env`, fill DB creds + `APP_URL` + SMTP, then
   `php artisan key:generate`.
7. **Database** — `php artisan migrate --force`, then seed `PermissionSeeder`, `RoleSeeder`,
   `ThemeSeeder` with `--force` (see the seeding note above).
8. **`php artisan storage:link`** — creates `public/storage` → `storage/app/public`. If the
   host blocks symlinks, create it via cPanel's *File Manager* or a tiny script
   (`symlink()`), or relocate the linked dir.
9. **Writable paths** — `storage/` and `bootstrap/cache/` must be writable (typically `755`).
10. **Scheduler cron** — one cron job, every minute, drives *all* scheduled commands
    (`backups:run`/`prune`/`monitor` from `routes/console.php`; the `telescope:prune` entry is
    `local`-gated and no-ops in production):

    ```cron
    * * * * * cd ~/laravel && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
    ```

11. **Queue worker (recommended)** — a second cron drains the database queue so heavy
    exports/imports/backups run **off-request**:

    ```cron
    * * * * * cd ~/laravel && /usr/local/bin/php artisan queue:work --stop-when-empty --tries=3 --max-time=55 >> /dev/null 2>&1
    ```

    `--stop-when-empty` + `--max-time=55` keeps each run short and lets the next minute's cron
    pick up. *Simplest fallback:* set `QUEUE_CONNECTION=sync` (no worker, jobs run inline) —
    but large exports/backups then risk a PHP `max_execution_time` timeout. Small exports run
    inline regardless via `EXPORT_SYNC_THRESHOLD`.
12. **Cache the config** — `php artisan optimize` (and `optimize:clear` before any later
    `.env` edit).

(Find the PHP CLI path with `which php` — cPanel often exposes a versioned binary like
`/opt/cpanel/ea-php84/root/usr/bin/php`; use that instead of `/usr/local/bin/php` if needed.)

## Decisions & why

- **`local` disks on cPanel, not public symlinks.** Downloads already stream through gated
  controller actions (never a public URL — see [Files & media](../features/files-and-media.md)),
  so `storage/app/private/*` is exactly right: outside the web root, served only after
  authorization. Nothing to expose.
- **DB-backed session/cache/queue.** They need zero extra services, which is the whole point
  on shared hosting where Redis/Memcached usually aren't available.
- **Cron worker over `sync`.** The app's heavy work is deliberately queued with status
  tracking ([Backups, exports & imports](../features/backups-exports-imports.md)). A
  cron-driven `queue:work` preserves that design without a long-running daemon; `sync` would
  collapse it back into the web request and time out on large jobs.
- **VPS stays self-contained.** Keeping MariaDB + SeaweedFS in containers means one `docker
  compose up` is the whole backend; managed services are an opt-in swap, not a prerequisite.

## Gotchas

- **Backups need the `mysqldump` / `mariadb-dump` binary.** The Docker image symlinks it
  (`docker/app/Dockerfile`); on cPanel run `which mysqldump` first. If it's absent, scheduled
  `backups:run` fails — disable the backup cron and rely on cPanel's own backups, or have the
  host install it. Keep `DB_BACKUP_SKIP_SSL=true` (shared MySQL rarely offers TLS).
- **`config:cache` freezes `env()`.** Once cached, `env()` outside `config/*` returns null —
  all env reads must stay in config files. Run `optimize:clear` after editing `.env`.
- **`APP_DEBUG=true` in production leaks stack traces.** It must be `false`.
- **Demo seeders are a security hole in production** — `UserSeeder` sets password = email. Seed
  only the structural seeders; create your admin by hand.
- **HTTPS & Sanctum.** Set `APP_URL=https://…` and `SESSION_SECURE_COOKIE=true`; if the SPA or
  mobile API answers on extra hostnames, add them to `SANCTUM_STATEFUL_DOMAINS`
  (see [Mobile API auth](../features/mobile-api-auth.md)).
- **The prod override keeps the bind mount.** `docker-compose.prod.yml` hardens config but the
  app code is still mounted from the host, so the host is the source of truth — deploy by
  `git pull` + rebuild assets, not by rebuilding an image. A fully immutable (baked-code)
  image is possible but adds an nginx static-asset-sync step; out of scope for this starter.

## Related

- [Services & stack](services-and-stack.md) — the dev stack these instructions harden.
- [Observability](observability.md) — JSON logs + `request_id`, the Pulse dashboard.
- [Backups, exports & imports](../features/backups-exports-imports.md) — what the scheduler runs.
- [0001 — Docker-only workflow](../decisions/0001-docker-only-workflow.md),
  [0005 — Settings as runtime config overrides](../decisions/0005-settings-runtime-config-overrides.md).
- `CLAUDE.md` § "Stack" / "Scheduled tasks".
