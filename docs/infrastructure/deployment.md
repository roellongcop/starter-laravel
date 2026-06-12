# Deployment

> Taking the stack to production on a Docker-capable VPS — hardening config, adding TLS,
> and locking down ports on top of the stack you already run locally.

## Purpose

The repo is built for local Docker development. Taking it to production on a Docker-capable
VPS is mostly *already done*: the `docker-compose.yml` stack already contains `queue` and
`scheduler` services, so most of the work is hardening config, adding TLS, and locking down
ports.

This room is the runbook for that.

## Key files

- `docker-compose.prod.yml` — VPS override (restart policy, locked-down ports).
- `.env.example` — the dev baseline; lines 22-25 carry the prod JSON-logging block.
- `routes/console.php` — the scheduled commands the `scheduler` service drives in production.
- `config/filesystems.php` — the `*_DISK_DRIVER` `s3`↔`local` switches.
- `config/database.php` — the backup `dump.exclude_tables` settings.

## How it works

### Production environment

The production `.env` differs from dev in these ways:

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

### Docker VPS (self-contained)

The base stack already runs `app`, `caddy`, `queue` (`queue:work`), `scheduler`
(`schedule:work`), `ssr` (the Inertia SSR renderer), `postgres`, and `seaweedfs` (+ the one-shot
`seaweedfs-init`). So the queue and scheduler "just work" — no cron needed. Layer the production
override on top:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

`docker-compose.prod.yml` flips `restart: always`, stops publishing the Postgres / SeaweedFS /
Adminer ports to the host (the app reaches them on the internal `appnet`), and parks
Adminer behind a `debug` profile. Only Caddy (`:80`) is exposed.

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

**TLS.** In production Caddy terminates HTTPS itself — `docker-compose.prod.yml` swaps the dev
`:80` Caddyfile for `docker/caddy/Caddyfile.prod`, which provisions and auto-renews a Let's
Encrypt cert for your domain and publishes `:80` (ACME challenge + HTTPS redirect) and `:443`.
No external reverse proxy needed. Set two env vars on the VPS and point `APP_URL` at the domain:

```dotenv
APP_DOMAIN=your-domain.com
TLS_EMAIL=admin@your-domain.com
APP_URL=https://your-domain.com
```

Point the domain's DNS A/AAAA record at the VPS, open ports 80 + 443 in the firewall, and bring
the stack up — Caddy fetches the cert on first boot and persists it in the `caddy-data` volume
(so restarts don't re-request it). `APP_URL=https://…` makes `AppServiceProvider` force https on
all generated links/assets. For multi-host or load-balanced setups you can instead keep the dev
`:80` Caddyfile and front the stack with a separate TLS proxy, but a single VPS doesn't need that.

**Persistence & backups.** All state lives in the named volumes `postgres-data` and
`seaweedfs-data`. The in-app nightly `backups:run` covers the **database**; the SeaweedFS
object store (uploads/exports/imports) is *not* in that dump — snapshot the `seaweedfs-data`
volume separately. See [Backups, exports & imports](../features/backups-exports-imports.md).

**Updates.** `git pull` on the host → `composer install --no-dev --optimize-autoloader` →
`npm run build` (rebuilds the client **and** SSR bundles) → `php artisan migrate --force` →
`php artisan optimize:clear && php artisan optimize` → `docker compose … restart app queue
scheduler ssr`.

**Optional — managed services.** To offload state: point `DB_HOST` at a managed database, set
`*_DISK_DRIVER=s3` with real `AWS_*` credentials/endpoint, and drop the `postgres` / `seaweedfs`
/ `seaweedfs-init` services from the override.

## Decisions & why

- **Gated downloads, never public URLs.** Downloads already stream through gated controller
  actions (never a public URL — see [Files & media](../features/files-and-media.md)), so
  `storage/app/private/*` stays outside the web root, served only after authorization.
- **DB-backed session/cache/queue.** They need zero extra services, so the base stack needs no
  Redis/Memcached to stand up.
- **VPS stays self-contained.** Keeping Postgres + SeaweedFS in containers means one `docker
  compose up` is the whole backend; managed services are an opt-in swap, not a prerequisite.

## Gotchas

- **Backups need the `pg_dump` / `psql` binaries.** The app image installs them via the
  `postgresql-client` package (`docker/app/Dockerfile`), so scheduled `backups:run` works out
  of the box. Point a managed Postgres at `DB_HOST` and the same binaries dump it over the wire.
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
  image is possible but adds a Caddy static-asset-sync step; out of scope for this starter.

## Related

- [Services & stack](services-and-stack.md) — the dev stack these instructions harden.
- [Observability](observability.md) — JSON logs + `request_id`, the Pulse dashboard.
- [Backups, exports & imports](../features/backups-exports-imports.md) — what the scheduler runs.
- [0001 — Docker-only workflow](../decisions/0001-docker-only-workflow.md),
  [0005 — Settings as runtime config overrides](../decisions/0005-settings-runtime-config-overrides.md).
- `CLAUDE.md` § "Stack" / "Scheduled tasks".
