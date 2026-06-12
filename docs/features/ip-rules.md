# IP rules

> Whitelist/blacklist IP enforcement, middleware-driven and settings-gated.

## Purpose

A settings-gated middleware that allows/denies requests by client IP. It's inert by default and reads
its toggle from `SystemSettings` (not controllers), so it can be turned on/off at runtime without a
deploy.

## Key files

- `app/Http/Middleware/EnforceIpRules.php` — blacklist always; whitelist when `whitelist_ip_only`.
- `app/Models/Ip.php` — the stored rules (`ips.ip_address` is unique).
- `bootstrap/app.php` — middleware registration.

## How it works

- `EnforceIpRules` looks up the client IP in the `Ip` table: any matching **Blacklist** entry → 403;
  when `whitelist_ip_only` is on, only **Whitelisted** IPs pass. Lookups are exact-match on
  `ip_address` (unique), so one row per IP. It's wrapped in try/catch so a missing table during early
  migration never hard-fails.

## Decisions & why

- **The toggle is read in middleware, not controllers** — see
  [ADR 0005 — settings as runtime config](../decisions/0005-settings-runtime-config-overrides.md).
- **Inert by default:** `whitelist_ip_only = false` and no blacklist rows means the IP gate is a no-op
  until explicitly configured.

## Gotchas

- **Behind Caddy, `$request->ip()` is the proxy IP unless `TrustProxies` is configured for the real
  client.** Enabling `whitelist_ip_only` without that can 403 everyone — configure `TrustProxies` in
  production first.

## Related

- [Settings](settings.md)
- `CLAUDE.md` § "User feedback" / middleware
