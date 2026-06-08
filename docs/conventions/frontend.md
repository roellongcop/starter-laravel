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
- `resources/js/lib/navHistory.ts`, `Components/Can.tsx`, `hooks/*`.

## How it works

- **All TypeScript.** shadcn primitives live in `resources/js/Components/ui/`; compose, don't
  fork them.
- **Shared Inertia props** (auth, navigation, bell, `settings.system`, theme, flash) come
  from `HandleInertiaRequests::share()` and are typed in `PageProps`. Read them with
  `usePage().props`.
- **UI gating:** wrap permission-gated UI in `<Can ability="...">` (fed by
  `auth.modules`/`auth.permissions`). The menu is roles-aware and can never show something
  the user can't access.
- **Navigation:** use `<BackButton fallback>` — it does a fresh `router.get` via a per-tab
  sessionStorage nav stack (`lib/navHistory.ts`), **not** `history.back()` (which serves
  Inertia's stale cache). See *Navigation history* below.
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

### Navigation history

`<BackButton>` does a **fresh** `router.get` rather than `window.history.back()` because Inertia
restores history navigations from its own cache (stale data). The previous URL comes from a
small per-tab stack in `lib/navHistory.ts`, kept in `sessionStorage` (survives refresh, supports
multi-level back).

The stack is driven by Inertia's **`navigate`** event: a navigation to the second-from-top URL
is treated as a back (pop), anything else as a forward (push) — no flags. **Caveat:** filter/sort
reloads use `{ replace: true }`, for which Inertia *skips* the `navigate` event, so the stack
mirrors those via the **`success`** event instead — when a visit lands on the same path as the
stack top but a different query string, it replaces the top (matching the browser's
`replaceState`).

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
