# Settings

> Typed, admin-editable settings groups — and exactly which setting is wired to what.

## Purpose

Expose a handful of runtime-tunable knobs (app name, timezone, page size, theme, security
toggles) through typed forms, and make each one actually change behavior at the right layer.

## Key files

- `app/Settings/*Settings.php` — the spatie/laravel-settings classes (`SystemSettings`,
  `EmailSettings`, `ImageSettings`, `NotificationSettings`); `SystemSettings::group()` =
  `'system'`.
- `database/settings/2026_06_03_000000_create_admin_settings.php` — the seeded defaults.
- `app/Http/Controllers/SettingsController.php` — group-keyed show/update.
- `app/Http/Requests/SettingsRequest.php` — per-group validation.
- `resources/js/Pages/Settings/Index.tsx` — the tabbed form.
- **Consumers:** `app/Providers/AppServiceProvider.php` (`applySystemSettings()`),
  `app/Http/Middleware/HandleInertiaRequests.php` (`appSettings()`),
  `app/Http/Middleware/{TrackVisitor,EnforceIpRules}.php`,
  `resources/js/hooks/use-idle-logout.ts`, `resources/js/Components/ThemeProvider.tsx`.

## How it works

Settings are stored/validated/displayed uniformly, but each one takes effect at a different
layer (see [ADR 0005](../decisions/0005-settings-runtime-config-overrides.md)). System group:

| Setting               | Wired? | Where it takes effect |
| --------------------- | ------ | --------------------- |
| `app_name`            | ✅ | Shared via Inertia (`settings.system.app_name`); shown in the header (`AuthenticatedLayout`). |
| `default_theme`       | ✅ | Shared via Inertia → `app.tsx` → `ThemeProvider` sets `data-theme` on `<html>`. |
| `timezone`            | ✅ | `AppServiceProvider::applySystemSettings()` → `config(['app.timezone'])` + `date_default_timezone_set()`. |
| `pagination_size`     | ✅ | `applySystemSettings()` → `config(['keen.pagination_size'])`; the 16 list controllers read that config key unchanged. |
| `auto_logout_seconds` | ✅ | Shared via Inertia; the `use-idle-logout` hook (mounted in `AuthenticatedLayout`) warns then `router.post(route('logout'))`. `0` = off. |
| `enable_visitor`      | ✅ | `TrackVisitor` middleware records visitors only when true. |
| `whitelist_ip_only`   | ✅ | `EnforceIpRules` middleware 403s non-whitelisted IPs when true. |

`applySystemSettings()` runs in `boot()` (after config loads, before controllers) and is
wrapped in `try/catch (\Throwable)` so a missing settings table during early migrations
falls back to config-file defaults. `SystemSettings` is a scoped binding, so the provider
and the Inertia middleware share one instance — no duplicate query.

### Email group

`EmailSettings` (`from_address`, `from_name`, `smtp_host`, `smtp_port`, `smtp_username`,
`smtp_password` (encrypted), `smtp_encryption`) drives the SMTP mailer via
`AppServiceProvider::applyEmailSettings()` — but **only when SMTP is the active transport**
(`config('mail.default') === 'smtp'`):

| Field | Applied to |
| ----- | ---------- |
| `from_address` / `from_name` | `config('mail.from.*')` |
| `smtp_host` / `smtp_port`    | `config('mail.mailers.smtp.host'/'port')` |
| `smtp_username` / `smtp_password` | `config('mail.mailers.smtp.username'/'password')` — only when set, so empty DB defaults don't clobber env |
| `smtp_encryption` (`tls`/`ssl`) | `config('mail.mailers.smtp.scheme')` — `ssl`→`smtps`, `tls`→`smtp` |

In dev (`MAIL_MAILER=log`/`array`) `applyEmailSettings()` returns early and the env mail
config is left untouched. **To use these settings in production, set `MAIL_MAILER=smtp`** —
then the stored host/port/credentials/from/encryption override env. The "blank password =
keep" behavior is handled in `SettingsController` (a blank field is unset before save so the
encrypted value is retained).

**Send test email.** The Email tab has a button (`POST settings.email.test` →
`SettingsController::testEmail`, gated by `settings.update`) that mails a `TestMail` to the
current admin using the configured transport. It tests the **saved** settings (applied to
config at boot) — save before testing. Success/failure surfaces via the flash→toast bag.

**Who else sends mail:** the public contact form (`ContactController::store`) mails a
`ContactMessage` to `EmailSettings::from_address` with Reply-To set to the visitor (and still
logs the submission for audit); export/import "ready" notifications use the `mail` channel.
All of them ride the same configured transport — so with the dev default they land in Mailpit
(`http://localhost:8025`).

### Image group — brand assets

`ImageSettings` holds three **File tokens**: `favicon_token`, `square_logo_token`,
`landscape_logo_token`. The Image tab uploads each via `<ImagePicker>` (→ `media.store` →
`{token,url}`) and stores the token. They're served by a **public** route
`GET /brand/{favicon|square-logo|landscape-logo}` (`BrandController` → `ImageStreamer`) so the
favicon and the login-screen logo load without auth — unlike the gated `media.img`. URLs are
shared as a dedicated `brand` Inertia prop (`{favicon_url, square_logo_url,
landscape_logo_url}`, cache-busted by token); the layouts render the favicon via `<Head>`, the
square logo in the header, and the landscape logo on the login screen, each falling back to the
bundled `<ApplicationLogo>` SVG.

Accepted image extensions are **not** a setting — they live in `config('keen.image_extensions')`
(read by `StoreMediaRequest`/`StoreFileRequest`) and the `<ImagePicker accept>` prop. The old
`max_width`/`max_height` fields were unused and were removed.

## Decisions & why

- [ADR 0005 — Settings as runtime config overrides](../decisions/0005-settings-runtime-config-overrides.md):
  config-shaped settings override `config()` at boot; controllers are *not* changed to read
  `SystemSettings` directly.

## Gotchas

- Validation must keep values safe to apply blindly — the boot override has no second guard.
  `timezone` uses the `timezone` rule; `pagination_size` is clamped 1–200; `auto_logout_seconds`
  is `min:0`.
- The idle-logout warning toast auto-dismisses after ~5s (the global toast delay), so it's a
  brief heads-up, not a persistent countdown.
- `whitelist_ip_only` matches `$request->ip()`, which behind nginx is the **proxy** IP unless
  `TrustProxies` is configured — turning it on without that (or without whitelisting the proxy
  IP) can lock everyone out. See [Visitor & IP](visitor-and-ip.md).
- After changing a setting, a cached config can mask config-shaped overrides: `php artisan
  config:clear`.
- Mail transport is env-driven like storage disks: `MAIL_MAILER` switches `log` ↔ `smtp`
  (Mailpit) the way `*_DISK_DRIVER` switches `local` ↔ `s3`. Dev defaults to `smtp` → Mailpit
  (inbox at `http://localhost:8025`); the EmailSettings override is **dormant** until an admin
  sets `smtp_host` (env is the base layer). The tests cover config application, not delivery.

## Related

- [Visitor tracking & IP rules](visitor-and-ip.md) · [Theming](theming.md)
- [ADR 0005](../decisions/0005-settings-runtime-config-overrides.md)
- `CLAUDE.md` § "User feedback" / Settings
