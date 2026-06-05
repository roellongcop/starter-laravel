# Visitor tracking & IP rules

> Cookie-based visitor logging and whitelist/blacklist IP enforcement, both middleware-driven.

## Purpose

Two independent, settings-gated middleware: one records anonymous visitor activity for analytics, the
other allows/denies requests by client IP. Both are inert by default and read their toggles from
`SystemSettings` (not controllers), so they can be turned on/off at runtime without a deploy.

## Key files

- `app/Http/Middleware/TrackVisitor.php` — records visitors when `enable_visitor` is on.
- `app/Http/Middleware/EnforceIpRules.php` — blacklist always; whitelist when `whitelist_ip_only`.
- `app/Models/{Visitor,VisitLog,Ip}.php` — the stored data (`ips.ip_address` is unique).
- `bootstrap/app.php` — middleware registration.

## How it works

- **Tracking.** `TrackVisitor` runs after the response. For **GET** page loads only (it skips
  `build/*`, `up`, `storage/*`) and only when `enable_visitor` is set, it upserts a cookie-keyed
  `Visitor` and logs a `PageView` `VisitLog`. It's wrapped so tracking can never break a request
  (fails open). Browser/OS/device come from `jenssegers/agent`.
- **IP rules.** `EnforceIpRules` looks up the client IP in the `Ip` table: any matching **Blacklist**
  entry → 403; when `whitelist_ip_only` is on, only **Whitelisted** IPs pass. Lookups are exact-match
  on `ip_address` (unique), so one row per IP. It's wrapped in try/catch so a missing table during
  early migration never hard-fails.

## Decisions & why

- **Both toggles are read in middleware, not controllers** — see
  [ADR 0005 — settings as runtime config](../decisions/0005-settings-runtime-config-overrides.md).
- **Inert by default:** `whitelist_ip_only = false` and no blacklist rows means the IP gate is a no-op
  until explicitly configured.

## Gotchas

- **Behind nginx, `$request->ip()` is the proxy IP unless `TrustProxies` is configured for the real
  client.** Enabling `whitelist_ip_only` without that can 403 everyone — configure `TrustProxies` in
  production first.
- Tracking is GET-only and fails open by design — a gap in visit logs is never an error.

## Related

- [Settings](settings.md)
- `CLAUDE.md` § "User feedback" / middleware
