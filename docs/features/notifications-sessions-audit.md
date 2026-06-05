# Notifications, sessions & audit

> The bell + notifications list, active-session management, the audit log, and the toast/idle-logout
> client plumbing.

## Purpose

A grab-bag of account-activity surfaces: in-app notifications (a navbar bell plus a full list), the
ability to see and revoke active sessions, and a read-only audit trail of model changes. The client
side adds a global toast store and an idle-logout timer.

## Key files

- `app/Http/Controllers/{NotificationController,SessionController,LogController}.php` — the resources.
- `app/Http/Middleware/HandleInertiaRequests.php` — shares the `bell` prop (unread count + recent).
- owen-it auditing on `BaseModel` — the audit trail source; `app/Models/Audit.php` enriches it with
  parsed browser/OS/device.
- `resources/js/hooks/use-toast.ts` — module-global toast store.
- `resources/js/hooks/use-idle-logout.ts` — inactivity auto-logout.

## How it works

- **Bell.** `HandleInertiaRequests::bell()` shares `{ unread_count, recent[] }` for the navbar. It's
  named `bell` (not `notifications`) so it can't collide with the Notifications index list prop. Guests
  get empty defaults.
- **Sessions.** `SessionController` lists the user's active sessions (from the DB session driver) and
  can revoke them.
- **Audit.** owen-it records create/update/delete on `BaseModel`s; `LogController` lists them and
  `Audit` derives browser/OS/device from the stored user-agent.
- **Toasts.** `use-toast.ts` is a small **module-global** store (adapted from shadcn/ui) so `toast()`
  works from anywhere — React components *and* plain callbacks like axios handlers and the Inertia
  flash bridge in `app.tsx`. (Usage: see [frontend conventions](../conventions/frontend.md).)
- **Idle logout.** `useIdleLogout()` reads the `auto_logout_seconds` SystemSetting (0 = off) shared via
  Inertia and is mounted once in `AuthenticatedLayout`. It warns shortly before the cutoff, then POSTs
  to logout; activity reschedules the timer (throttled — see the inline notes in the hook).

## Decisions & why

- **The bell prop is deliberately separate from the list** to keep partial reloads of one from
  clobbering the other.
- **Idle-logout timing is driven by a setting, not hard-coded** — see
  [ADR 0005 — settings as runtime config](../decisions/0005-settings-runtime-config-overrides.md).
- **A global toast store rather than React context** so non-React code paths (axios, the flash bridge)
  can raise toasts without a provider in scope.

## Gotchas

- The audit log is read-only — never write to it directly.
- `useIdleLogout` is a side-effect-only hook; mount it exactly once (in the authenticated layout) or
  timers stack.

## Related

- [Frontend conventions](../conventions/frontend.md) — toasts/flash bridge.
- [Settings](settings.md) — `auto_logout_seconds`.
- `CLAUDE.md` § "User feedback"
