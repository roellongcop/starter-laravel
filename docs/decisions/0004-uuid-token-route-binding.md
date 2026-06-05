# 0004 — UUID token route binding

**Status:** accepted

## Context

Exposing auto-increment ids in URLs (`/users/42`) leaks row counts and invites
enumeration. The frontend shouldn't need — or see — database primary keys.

## Decision

Domain models bind routes by a **UUID `token`** column, not `id`. Routes resolve
`/{resource}/{token}`; serializers expose `token` (never `id`) to Inertia/React. Ids stay
server-side.

## Consequences

- Every domain table has an indexed `token`; models set their route key name to `token`.
- Frontend types use `token: string` as the identifier (see `resources/js/types/index.d.ts`
  — `AdminUser`, `AdminRole`, etc.).
- Route binding composes with [0003](0003-record-status-not-soft-deletes.md): binding lifts
  the `active` scope so inactive rows are still reachable by token for edit/delete.

## Related

- [Backend conventions](../conventions/backend.md)
- Auto-memory: `token-route-keys.md`
