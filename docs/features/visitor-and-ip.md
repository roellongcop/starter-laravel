# Visitor tracking & IP rules

> Cookie-based visitor logging and whitelist/blacklist IP enforcement, both middleware-driven.

> **TODO** — stub. Fill in when next touching this area, following [`TEMPLATE.md`](../TEMPLATE.md).

## Purpose

_TODO._

## Key files

- `app/Http/Middleware/TrackVisitor.php` — records visitors when `enable_visitor` is on.
- `app/Http/Middleware/EnforceIpRules.php` — blacklist always; whitelist when `whitelist_ip_only`.
- `app/Models/{Visitor,VisitLog,Ip}.php`; `bootstrap/app.php` (middleware registration).

## How it works

_TODO — `TrackVisitor` upserts a cookie-keyed `Visitor` + logs `PageView` (GET only, skips
build/up/storage, fails open); `EnforceIpRules` checks the `Ip` table by `list_type`._

## Decisions & why

- Both settings are read in middleware, not controllers — see
  [ADR 0005](../decisions/0005-settings-runtime-config-overrides.md).

## Gotchas

- Behind nginx, `$request->ip()` is the **proxy** IP unless `TrustProxies` is configured —
  enabling `whitelist_ip_only` without that can 403 everyone. **TODO:** document/configure
  `TrustProxies` for production.

## Related

- [Settings](settings.md)
- `CLAUDE.md` § "record_status" / middleware
