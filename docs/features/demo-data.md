# Demo data seeder

> A one-command firehose of large, combinatorial fake data for stress-testing the UI, filters and pagination — separate from the default seed.

## Purpose

The default seeders (`make setup`/`make fresh`) create a small, hand-curated dataset: a
few demo accounts, a handful of orgs/projects/assets. That's right for a first run, but it
never surfaces the problems that only appear at volume:

- list/pagination performance (keyset cursor pages, infinite scroll, the org filter `<select>`),
- every filter and badge colour (each enum/status value present, active **and** inactive rows),
- UI truncation/overflow (very long names, descriptions, addresses, tag lists),
- date-range filters (rows spread across a year).

`DemoSeeder` exists to generate exactly that — on demand, never as part of the default chain.

## Key files

- `database/seeders/DemoSeeder.php` — the seeder. Standalone; **not** referenced by
  `DatabaseSeeder`.
- `Makefile` → `seed-demo` target — `docker compose exec … php artisan db:seed --class=DemoSeeder`,
  with a `DEMO_SCALE` passthrough.

## How it works

Run it (best on a freshly-seeded DB):

```bash
make seed-demo                  # ~1000 rows per entity (default)
make seed-demo DEMO_SCALE=200   # smaller/faster run
```

`DEMO_SCALE` (read via `getenv`, default 1000) is the per-entity target volume. For each
major entity the seeder writes `DEMO_SCALE` rows, cycling attributes so the data is
**combinatorial**:

- **Enums** — every case of `ProjectStatus`, `UserStatus`, `IpListType`, the
  `Backup`/`UserImport`/`UserExport` statuses, `AuthEvent` and `NotificationType` appears,
  so every filter option and badge colour is represented.
- **record_status** — ~1 in 6 rows is Inactive, so the *Show inactive* toggle has data and
  active lists are still large.
- **Long text** — every 40th name and a slice of descriptions/addresses use a capped long
  string (≤230 chars, safe for `varchar(255)`); notification messages and form answers use a
  larger blob (those are TEXT/JSON columns). This surfaces card/table/badge overflow.
- **Dates** — `created_at` is spread across the last 365 days for date-range filters.
- **Pivots** — `taggables` (org-scoped tags on projects/assets/forms/reference files),
  `project_assets` (with cycled per-binding `status`), and team members are all populated.

Seeding runs parent-before-child: users → (themes, ips, files, backups, imports/exports,
login history, notifications) → organizations → org-scoped entities (tags, categories, org
roles, projects, assets, forms, reference files) → tag/asset pivots → teams → people →
form responses. Org-scoped children are spread across the first `RICH_ORGS` (50)
organizations so per-org filters stay dense while the global lists hit full scale.

## Decisions & why

- **Not in the default chain.** It's a heavy, opt-in tool. `DatabaseSeeder` stays small and
  fast; you reach for `make seed-demo` only when you want volume.
- **Deterministic uniqueness, never `fake()->unique()`.** Faker's unique pool overflows past
  a few thousand calls. Unique columns (emails, names, `id_code`, IPs…) are built from a
  loop index plus a per-run id, so any scale is safe — and the per-run id means the seeder is
  safe to **run repeatedly** without colliding with earlier runs.
- **Model events stay enabled.** Domain rows need the `HasToken` `creating` hook for their
  NOT-NULL unique `token`, so the seeder never disables events. `Blameable` sets
  `created_by`/`updated_by` to `null` (no auth in a seeder) — fine. Auditing is already off
  for console commands (`config/audit.php` → `audit.console = false`), so it won't bloat the
  `audits` table. See [record_status ADR](../decisions/0003-record-status-not-soft-deletes.md)
  and [token route binding ADR](../decisions/0004-uuid-token-route-binding.md).
- **Bulk insert where it's safe, Eloquent where it matters.** Users, notifications and the
  pivots are bulk-inserted (one shared bcrypt hash for all demo users — avoids thousands of
  slow hashes); everything else uses `Model::create()` (unguarded) so casts/columns/defaults
  and the token hook are handled correctly. Loops run inside `DB::transaction()` for speed.

## Gotchas

- **Run on a fresh DB for a clean baseline.** It appends, it doesn't reset — `make fresh`
  first if you want only demo data. (It *is* safe to re-run thanks to the per-run id.)
- **It adds a lot of rows.** At the default scale that's ~20k+ domain rows plus pivots; the
  org filter `<select>` will list every seeded organization (intentional — that's part of the
  stress test). Lower `DEMO_SCALE` for a lighter run.
- **Long text is capped to fit `varchar(255)`** for string columns (name/address/description);
  only TEXT/JSON columns (notification `data`, form `answers`) get the longer blob. If you add
  a column with a tighter limit, keep that in mind.
- **Keep it in sync with new entities.** When you add a domain model, add a `seed*()` method
  (and any pivot) here so the demo set stays complete.

## Related

- [`docs/conventions/backend.md`](../conventions/backend.md) — `BaseModel` traits, factories
  and the `auditColumns()` footer the seeder writes to.
- [`docs/decisions/0003-record-status-not-soft-deletes.md`](../decisions/0003-record-status-not-soft-deletes.md) — the Active/Inactive toggle the seeder varies.
- [`docs/decisions/0004-uuid-token-route-binding.md`](../decisions/0004-uuid-token-route-binding.md) — why model events must stay on during seeding.
- `CLAUDE.md` § Testing / demo logins — the quick-ref version.
