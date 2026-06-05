# Users, roles & permissions

> CRUD for users and roles, a declared permission registry, and the roles-aware sidebar.

## Purpose

Authorization is driven by a **declared registry** (`config/permissions.php`), not ad-hoc checks:
abilities are synced into spatie/laravel-permission and gated uniformly in policies, route
middleware, and the React UI. Roles additionally carry presentation metadata (which buttons show,
what the sidebar looks like), and a user's effective menu is always the intersection of their roles'
menus with what they're actually allowed to access — so the UI can never offer something the
backend would reject.

## Key files

- `config/permissions.php` — the declared ability registry (crud / readonly / custom / standalone).
- `app/Support/Permissions.php` — expands that registry into concrete names
  (`users.index`, `view-inactive`, …); shared by the sync command, seeders, and Navigation.
- `app/Policies/BasePolicy.php` — permission-backed policy skeleton; each ability maps to
  `"{resource}.{action}"` via `$user->can()`.
- `app/Policies/RolePolicy.php` — roles policy (+ the system-role protection note below).
- `app/Models/Role.php` — extends spatie's Role with `role_type`, `module_access`,
  `main_navigation`, `priority`.
- `app/Enums/SystemRole.php` — the fixed seeded role names (one source of truth).
- `app/Support/Navigation.php` — derives `module_access` + the sidebar tree from a role's permissions,
  and merges a user's roles into one menu.
- `app/Http/Controllers/{UserController,RoleController}.php` — the resources (RoleController also holds
  system-role protection).
- `resources/js/Components/MenuBuilder.tsx` — drag-and-drop editor for a role's `main_navigation`.

## How it works

- **Registry → permissions.** `Permissions::map()/all()` expand `config/permissions.php` into
  permission names. `PermissionSeeder` runs `permissions:sync` (not per request), so the DB always
  matches the declared registry. Abilities are `"{resource}.{ability}"` plus standalone names like
  `view-inactive`.
- **Gating.** `BasePolicy` checks `$user->can("{key}.{action}")`; concrete policies only declare their
  `key()`. The frontend mirrors this with `<Can ability="…">`, fed by `auth.modules` /
  `auth.permissions` shared from `HandleInertiaRequests`. `Gate::before` grants the `developer` role
  god-mode.
- **module_access.** `Navigation::modulesFor()` groups a permission list into
  `{ resourceKey: [abilities] }`; React uses it to show/hide buttons (an Edit button needs
  `module_access.users` to include `"update"`).
- **Sidebar.** `Navigation::template()` is the full menu; `navigationFor()` filters it to accessible
  modules (a group survives if any child does). A role may instead store a custom `main_navigation`
  (built via `<MenuBuilder>`, which parses the stored `NavItem[]` into builder state and serializes it
  back on save). `Navigation::forUser()` merges all of a user's roles' menus by `priority` (highest
  wins on conflicts) and then **intersects** with their accessible modules.

## Decisions & why

- **System-role protection (no delete/rename) lives in `RoleController`, not `RolePolicy`.** The
  `developer` role bypasses policies via `Gate::before`, so the guard must live *outside* the gate to
  be bypass-proof.
- **`SystemRole` is the single source of truth for fixed role names**, referenced by `Gate::before`,
  registration, and seeders — so they can't drift apart by a typo or casing.
- **No `HasRecordStatus` global scope on `Role`** — spatie resolves roles through the model during
  permission checks, and a hidden-by-default scope could mask grants.
- **Sidebar items use plain `href` paths, not Ziggy route names**, so the menu renders even before a
  resource's routes exist.
- See [ADR 0004 — UUID token route binding](../decisions/0004-uuid-token-route-binding.md) and
  [ADR 0003 — record_status](../decisions/0003-record-status-not-soft-deletes.md).

## Gotchas

- Adding a resource means adding it to `config/permissions.php` **and** (if it should appear) to
  `Navigation::template()` — the keys must match.
- The merged menu can only ever *narrow* via `intersect()`; granting menu access without the matching
  permission shows nothing.
- Editing a role's permissions changes derived `module_access`/menus for every user holding it.

## Related

- [Backend conventions](../conventions/backend.md) — controller→FormRequest→Policy shape, `<Can>` gating.
- `CLAUDE.md` § "Authorization" and § "Sidebar navigation"
