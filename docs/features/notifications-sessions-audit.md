# Notifications, sessions & audit

> The bell + notifications list, active-session management, and the audit log.

> **TODO** — stub. Fill in when next touching this area, following [`TEMPLATE.md`](../TEMPLATE.md).

## Purpose

_TODO._

## Key files

- `app/Http/Controllers/{NotificationController,SessionController,LogController}.php`.
- `app/Http/Middleware/HandleInertiaRequests.php` — shares the `bell` prop (unread + recent).
- owen-it auditing on `BaseModel` — the audit trail source.

## How it works

_TODO — bell prop (named `bell` to avoid colliding with the Notifications index list),
session revoke, audit log listing._

## Decisions & why

_TODO._

## Gotchas

_TODO._

## Related

- [Backend conventions](../conventions/backend.md)
- `CLAUDE.md` § "User feedback" / auditing
