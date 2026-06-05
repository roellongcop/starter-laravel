# Docs — the memory palace

The canonical long-form documentation for this starter. Each topic is a **room**: one
file, one subject, the same shape every time. Walk the rooms to recall how and *why* the
project is built the way it is.

## How the docs fit together

Three surfaces, one rule — **single source of truth per fact**:

- **[`README.md`](../README.md) — the front door.** What the project is, quickstart,
  feature list. Links *into* these docs; doesn't duplicate them.
- **`docs/` — the canonical long-form (you are here).** Rationale, mechanisms, per-feature
  deep dives. The place a fact lives.
- **[`CLAUDE.md`](../CLAUDE.md) — the agent quick-ref.** Dense, auto-loaded by Claude Code.
  Stays self-contained but cross-links here for the deep version.

Every room follows [`TEMPLATE.md`](TEMPLATE.md): **Purpose → Key files → How it works →
Decisions & why → Gotchas → Related**. When you add a room, copy the template and link it
from the map below.

## The map

### Decisions (ADRs) — *why we chose X*

- [0001 — Docker-only workflow](decisions/0001-docker-only-workflow.md) — no host
  PHP/Node/Composer; everything behind a `Makefile`.
- [0002 — Keyset (cursor) pagination](decisions/0002-keyset-cursor-pagination.md) — no
  page numbers; `cursorResponse()` + `<CursorPager>`.
- [0003 — record_status, not soft deletes](decisions/0003-record-status-not-soft-deletes.md)
  — a business Active/Inactive toggle.
- [0004 — UUID token route binding](decisions/0004-uuid-token-route-binding.md) — domain
  models bind by `token`; ids never reach the frontend.
- [0005 — Settings as runtime config overrides](decisions/0005-settings-runtime-config-overrides.md)
  — how stored SystemSettings change live behavior.

### Conventions — *codestyle*

- [Backend](conventions/backend.md) — controller→FormRequest→Policy→Inertia shape,
  `BaseModel` traits, Larastan `@property` rules, `cursorResponse()`.
- [Frontend](conventions/frontend.md) — React/TS, shadcn primitives, Prettier import
  ordering + `eslint --fix`, `<BackButton>`/nav history, `<Can>` gating.

### Infrastructure — *how it's wired*

- [Services & stack](infrastructure/services-and-stack.md) — Docker services, ports,
  queue/scheduler, storage disks, `dated_path()`, gated downloads.
- [CI & hooks](infrastructure/ci-and-hooks.md) — the `make is-mergeable` gate, the
  `make hooks` pre-commit hook, and the code-quality tools.

### Features — *per-feature deep dives*

- [Settings](features/settings.md) — typed settings groups and exactly which settings are
  wired, and how. ✅ written
- [Users, roles & permissions](features/users-roles-permissions.md) — _stub_
- [Files & media](features/files-and-media.md) — _stub_
- [Backups, exports & imports](features/backups-exports-imports.md) — _stub_
- [Theming](features/theming.md) — _stub_
- [Visitor tracking & IP rules](features/visitor-and-ip.md) — _stub_
- [Notifications, sessions & audit](features/notifications-sessions-audit.md) — _stub_

> Stubs carry the template headings and a `TODO`. Fill a room when you next touch that
> area — leave it better-documented than you found it.
