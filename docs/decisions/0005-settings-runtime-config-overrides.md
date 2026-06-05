# 0005 — Settings as runtime config overrides

**Status:** accepted

## Context

`SystemSettings` (spatie/laravel-settings) stores admin-editable values, but several were
stored and validated yet never read — pure UI that changed nothing (`timezone`,
`pagination_size`, `auto_logout_seconds`). The question: how does a stored setting actually
take effect, given the codebase reads static `config()` everywhere?

## Decision

A setting becomes functional by being applied at the **right layer**, not by controllers
reading `SystemSettings` directly:

- **Config-shaped settings** (`timezone`, `pagination_size`) are applied as **runtime
  `config()` overrides** in `AppServiceProvider::applySystemSettings()`, called from
  `boot()` — after config loads, before any controller/middleware. The 16 controllers keep
  reading `config('keen.pagination_size')`; nothing about them changes.
- **Request-gating settings** (`enable_visitor`, `whitelist_ip_only`) are read by
  **middleware** (`TrackVisitor`, `EnforceIpRules`).
- **Frontend-behavior settings** (`default_theme`, `auto_logout_seconds`) are **shared via
  Inertia** (`HandleInertiaRequests::appSettings()` → `settings.system.*`) and consumed in
  React (`ThemeProvider`, the `use-idle-logout` hook).

All boot-time/middleware reads are wrapped in `try/catch (\Throwable)` because the settings
table may not exist during early migrations (fail safe to config-file defaults).

## Consequences

- New "config-like" settings follow the same pattern: store → validate → apply in
  `applySystemSettings()`; don't sprinkle `app(SystemSettings::class)` through controllers.
- `SystemSettings` is a scoped binding (one instance per request), so the provider and the
  Inertia middleware share it — no duplicate query.
- Validation (`SettingsRequest`) must keep values safe to apply blindly (e.g. `timezone`
  rule, `pagination_size` clamp) since the override has no second guard.
- `EmailSettings` follows the same boot-override pattern via `applyEmailSettings()`, but
  **SMTP-gated**: it only writes `config('mail.*')` when `config('mail.default') === 'smtp'`,
  so dev's `log` mailer is untouched and prod opts in with `MAIL_MAILER=smtp`. It also
  **layers on env** — host/port/credentials are overridden only when `smtp_host` is set
  (seeded empty), so env (`MAIL_HOST=mailpit` in dev) is the base and the Email tab is the
  production override, mirroring how a storage disk's env driver is the base for s3 config.

## Related

- [Settings feature](../features/settings.md) — the per-setting wiring table.
- `CLAUDE.md` § "User feedback" / Settings
