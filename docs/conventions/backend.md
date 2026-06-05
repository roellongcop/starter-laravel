# Backend conventions

> How a new resource is shaped server-side, and the rules that keep Larastan/Pint green.

## Purpose

Keep every resource consistent so a reader who knows one controller knows them all, and so
static analysis and formatting never break the build.

## Key files

- `app/Http/Controllers/*` — one controller per resource, authorizing per method.
- `app/Http/Requests/*` — `BaseFormRequest` subclasses (validation + authorization).
- `app/Policies/*` — spatie-permission-backed gates.
- `app/Models/BaseModel.php` + `app/Models/Concerns/*` — the shared model base.
- `app/Support/helpers.php` — `cursorResponse()`, `dated_path()`.
- `config/permissions.php` — the declared ability registry.

## How it works

- **Resource shape:** controller → FormRequest → Policy → Inertia page. Lists follow the
  keyset + `cursorResponse()` pattern ([ADR 0002](../decisions/0002-keyset-cursor-pagination.md)).
  Bulk and `destroy` actions redirect with `back()` to preserve filters.
- **Models** extend `BaseModel`, which composes `IsResource` + `HasRecordStatus` +
  `Blameable` + owen-it auditing. `User` can't extend it (it's `Authenticatable`), so it
  uses the same traits directly. Tables are plural snake_case, no prefix; a model sets
  `$table` only to override (e.g. `UserMeta` → `user_meta`).
- **Authorization** is spatie/laravel-permission driven by `config/permissions.php`, synced
  via the `permissions:sync` command (run by `PermissionSeeder`, not per request).
  Abilities are `"{resource}.{ability}"` plus standalone `view-inactive`. `Gate::before`
  gives the `developer` role god mode.
- **Settings that affect behavior** are applied at the boot/middleware/Inertia layer, not
  read ad-hoc in controllers — see [ADR 0005](../decisions/0005-settings-runtime-config-overrides.md).

## Decisions & why

- [0002 Keyset pagination](../decisions/0002-keyset-cursor-pagination.md),
  [0003 record_status](../decisions/0003-record-status-not-soft-deletes.md),
  [0004 UUID tokens](../decisions/0004-uuid-token-route-binding.md).

## Gotchas

- **Larastan `@property`:** a model with any `@property` needs a *complete* set (incl.
  `created_at`/`updated_at`) or Larastan fails. `checkModelProperties` is off. Avoid generic
  `Attribute<...>` return docblocks (covariance error).
- After changing controllers/config, a cached config can mask it: `make shell` →
  `php artisan config:clear`.
- Run `make pint` (format) + `make stan` (analyse) before considering a change done — or
  `make is-mergeable` for the whole gate.

## Related

- [Frontend conventions](frontend.md)
- [CI & hooks](../infrastructure/ci-and-hooks.md)
- `CLAUDE.md` § "Architecture & cross-cutting conventions"
