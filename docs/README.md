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
  ordering + `eslint --fix`, breadcrumbs (`<PageHeader breadcrumbs>`), `<Can>` gating.
- [Comments](conventions/comments.md) — comment the *why*, not the *what*; what to
  keep vs delete, and the cleanup baseline.

### Infrastructure — *how it's wired*

- [Services & stack](infrastructure/services-and-stack.md) — Docker services, ports,
  queue/scheduler, storage disks, `dated_path()`, gated downloads.
- [CI & hooks](infrastructure/ci-and-hooks.md) — the `make is-mergeable` gate, the
  `make hooks` pre-commit hook, and the code-quality tools.
- [Observability](infrastructure/observability.md) — structured logs + `request_id` correlation,
  the Pulse dashboard, and where tracing goes next.
- [Deployment](infrastructure/deployment.md) — taking it to production on a Docker-capable VPS.

### Features — *per-feature deep dives*

- [Settings](features/settings.md) — typed settings groups and exactly which settings are
  wired, and how. ✅ written
- [Users, roles & permissions](features/users-roles-permissions.md) — declared registry,
  policies, roles-aware sidebar. ✅ written
- [Files & media](features/files-and-media.md) — uploads, Glide resizing, gated downloads,
  the viewer. ✅ written
- [Backups, exports & imports](features/backups-exports-imports.md) — queued jobs, status
  tracking, the restore gate. ✅ written
- [Theming](features/theming.md) — CSS-variable light/dark + admin token palettes. ✅ written
- [IP rules](features/ip-rules.md) — whitelist/blacklist IP allow/deny. ✅ written
- [Notifications, sessions & audit](features/notifications-sessions-audit.md) — bell,
  sessions, audit log, toasts/idle-logout. ✅ written
- [Mobile API auth](features/mobile-api-auth.md) — stateless Sanctum bearer-token
  login/me/logout for the `/api/v1` surface. ✅ written
- [SEO & SSR](features/seo-and-ssr.md) — selective Inertia SSR for public pages, the `Seo`
  meta object, sitemap/robots. ✅ written
- [Demo data seeder](features/demo-data.md) — `make seed-demo`: bulk combinatorial fake data
  for UI/filter/performance testing, separate from the default seed. ✅ written

> Fill a room when you next touch that area — leave it better-documented than you found it.
