# 0003 — record_status, not soft deletes

**Status:** accepted

## Context

Admins need to deactivate rows (hide a user, retire an IP rule) without destroying them,
and to bring them back. Laravel's `SoftDeletes` models "deleted" — semantically wrong for a
business Active/Inactive toggle, and it overloads the `deleted_at` column with meaning.

## Decision

Every domain table carries an unsigned-tinyint **`record_status`** (Active/Inactive),
added by the `auditColumns()` macro alongside `created_by`/`updated_by`/timestamps. The
`HasRecordStatus` trait (`app/Models/Concerns/`) provides:

- a global `active` scope (inactive rows hidden by default),
- `withInactive()` / `onlyInactive()` / `activate()` / `inactivate()` and a static
  `bulkAction()`,
- an override of `resolveRouteBindingQuery()` that **drops the active scope for route
  binding** so `/users/{inactive}/edit` resolves instead of 404ing.

## Consequences

- Listing inactive rows is gated by the `view-inactive` permission.
- This is **not** soft deletes — a real `destroy()` still hard-deletes. Inactive ≠ deleted.
- Any new domain model gets this for free via `BaseModel`; `User` (an `Authenticatable`)
  applies the traits directly.

## Related

- [Users, roles & permissions](../features/users-roles-permissions.md)
- `CLAUDE.md` § "record_status is a business Active/Inactive toggle"
