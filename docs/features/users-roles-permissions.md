# Users, roles & permissions

> CRUD for users/roles, the declared permission registry, and the roles-aware sidebar.

> **TODO** — stub. Fill in when next touching this area, following [`TEMPLATE.md`](../TEMPLATE.md).

## Purpose

_TODO._

## Key files

- `app/Http/Controllers/{UserController,RoleController}.php`
- `config/permissions.php` — declared ability registry, synced via `permissions:sync`.
- `App\Support\Navigation` — derives `module_access` + sidebar tree from a role's permissions.

## How it works

_TODO — declared-registry authorization, `Gate::before` developer god-mode, `main_navigation`
menu builder, inline UserMeta._

## Decisions & why

- [ADR 0003 — record_status](../decisions/0003-record-status-not-soft-deletes.md)
- [ADR 0004 — UUID token route binding](../decisions/0004-uuid-token-route-binding.md)

## Gotchas

_TODO._

## Related

- [Backend conventions](../conventions/backend.md)
- `CLAUDE.md` § "Authorization" / "Sidebar navigation"
