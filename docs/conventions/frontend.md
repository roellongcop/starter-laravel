# Frontend conventions

> How React/TypeScript code is structured, and the formatting/lint rules CI enforces.

## Purpose

Keep the Inertia + React + TypeScript layer consistent and lint-clean so edits don't churn
imports or fail the format check.

## Key files

- `resources/js/Pages/*` — Inertia page components (one per route).
- `resources/js/Layouts/*` — `AuthenticatedLayout` (app shell), `GuestLayout` (auth pages).
- `resources/js/Components/ui/*` — shadcn primitives; other components alongside.
- `resources/js/types/index.d.ts` — shared types incl. `PageProps` (shape of Inertia
  shared props).
- `resources/js/Components/Breadcrumbs.tsx`, `Components/Can.tsx`, `hooks/*`.

## How it works

- **All TypeScript.** shadcn primitives live in `resources/js/Components/ui/`; compose, don't
  fork them.
- **Shared Inertia props** (auth, navigation, bell, `settings.system`, theme, flash) come
  from `HandleInertiaRequests::share()` and are typed in `PageProps`. Read them with
  `usePage().props`.
- **UI gating:** wrap permission-gated UI in `<Can ability="...">` (fed by
  `auth.modules`/`auth.permissions`). The menu is roles-aware and can never show something
  the user can't access.
- **Navigation / wayfinding:** non-index pages (Show/Create/Edit) pass a `breadcrumbs`
  `Crumb[]` trail to `<PageHeader>`, rendered above the title by `Components/Breadcrumbs.tsx`
  (the last crumb is the current page, no link; earlier crumbs link to ancestors). Index/list
  pages keep the plain-text `description` instead. See *Breadcrumbs* below.
- **Pagination:** `<CursorPager>` renders Prev/Next only — no page numbers, no sortable
  headers (lists are ordered `created_at DESC, id DESC` server-side; see
  [ADR 0002](../decisions/0002-keyset-cursor-pagination.md)). It navigates by pushing the
  opaque cursor onto the *current* URL, **merging** existing query params (search, `inactive`,
  …) so the filtered result set the cursor was computed against stays the same — sending only
  the cursor would re-run an unfiltered query and the keyset position would point into the
  wrong set.
- **Toasts:** controllers flash `success`/`error`; `app.tsx` turns the shared `flash` prop
  into toasts. From client code call `toast()` (`hooks/use-toast.ts`) directly.

## Decisions & why

- Theming is CSS-variable + `data-theme` based — see [Theming](../features/theming.md).

### Breadcrumbs

Non-index pages orient the user with a breadcrumb trail instead of a back button. A page passes
`breadcrumbs={[...]}` (a `Crumb[]`, `{ label, href? }`) to `<PageHeader>`, which renders it above
the title via `Components/Breadcrumbs.tsx`. Trails start at the resource index (no "Dashboard"
root) and end at the current page (a label with no `href`):

- **Show:** `[{ label: 'Users', href: route('users.index') }, { label: user.name }]`
- **Edit:** add the entity as a linked middle crumb, then `{ label: 'Edit' }` — so both the
  index and the entity are one click away.
- **Create:** `[{ resource index }, { label: 'New X' }]`.

Crumbs are real Inertia `<Link>`s. Index/list pages do **not** use breadcrumbs — they keep the
plain-text `description`.

## Gotchas

- **Prettier owns import order and class order** (`prettier-plugin-organize-imports` +
  `prettier-plugin-tailwindcss`). Manually-ordered imports get reshuffled — run
  `eslint --fix` / `npm run format` after edits, and don't hand-sort.
- The format/lint checks (`format:check`, `lint:check`) target **`resources/js` only**, so
  Markdown and PHP are out of scope for Prettier/ESLint.
- The `node` container runs as root → `npm run build` writes root-owned files under
  `public/build` (see [ADR 0001](../decisions/0001-docker-only-workflow.md)).
- Install the pre-commit hook (`make hooks`) so a bad format can't reach CI — see
  [CI & hooks](../infrastructure/ci-and-hooks.md).

## Related

- [Backend conventions](backend.md)
- [Commenting conventions](comments.md)
- `CLAUDE.md` § "Conventions when editing"
