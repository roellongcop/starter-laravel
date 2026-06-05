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
  Inertia's stale cache).
- **Toasts:** controllers flash `success`/`error`; `app.tsx` turns the shared `flash` prop
  into toasts. From client code call `toast()` (`hooks/use-toast.ts`) directly.

## Decisions & why

- Theming is CSS-variable + `data-theme` based — see [Theming](../features/theming.md).

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
- `CLAUDE.md` § "Conventions when editing"
