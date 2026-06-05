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

## Related

- [Visitor tracking & IP rules](visitor-and-ip.md) · [Theming](theming.md)
- [ADR 0005](../decisions/0005-settings-runtime-config-overrides.md)
- `CLAUDE.md` § "User feedback" / Settings
