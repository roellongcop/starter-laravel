# CI & hooks

> The quality gate — what CI runs, how to mirror it locally, and the pre-commit hook.

## Purpose

Keep `master` green by running the same checks in three places: locally on demand
(`make is-mergeable`), at commit time (the pre-commit hook), and in CI.

## Key files

- `.github/workflows/ci.yml` — the CI pipeline.
- `Makefile` — `pint`, `stan`, `test`, `lint`, `is-mergeable`, `hooks` targets.
- `.githooks/pre-commit` — the committed hook script.
- `pint.json`, `phpstan.neon` (+ `phpstan-baseline.neon`), `.eslintrc`/`prettier` config.

## How it works

**The gate** (identical backend + frontend checks everywhere):

- Backend: Pint (`--test`), Larastan (level 5, with baseline), Pest.
- Frontend: Prettier (`format:check`), ESLint (`lint:check`), production build.

`make is-mergeable` runs that whole gate locally, **check-only (no writes)** — the same
thing CI enforces. Run it as the *last* step before pushing.

To auto-fix style issues instead of just being told about them, run **`make fix`** (Pint +
ESLint `--fix` + Prettier `--write` — this **writes files**), then `make is-mergeable` to
verify. `make is-mergeable` and the pre-commit hook stay check-only; `make fix` is the only
write-mode formatter.

**The pre-commit hook** (`.githooks/pre-commit`): runs Pint + Prettier + ESLint
(check-only) inside the containers before each commit, so a malformed edit can't reach CI.
Enable it once per clone:

```bash
make hooks            # sets git config core.hooksPath .githooks
```

It needs the `app` container running (for Pint) and uses `docker compose run --rm node` for
the frontend checks. Each step prints a fix hint on failure (e.g. "run `make lint`"). Bypass
a single commit with `SKIP_HOOKS=1 git commit …`.

## Decisions & why

- The hook and `make is-mergeable` deliberately share CI's exact commands so "passes
  locally" means "passes CI". The hook is **check-only**, not auto-fix, for predictable
  commits — fix with `make pint` / `make lint` (which *do* write).
- `core.hooksPath` is per-clone local config (not committed state), which is why enabling is
  a one-time `make hooks` per clone rather than automatic.

## Gotchas

- The gate validates the **snapshot at the moment you run it** — an edit made *after* a
  passing `make is-mergeable` is unchecked until you run it again. (This is exactly why the
  hook exists: it catches late edits at commit time.)
- `format:check`/`lint:check` cover **`resources/js` only** — Markdown (these docs) and PHP
  are not Prettier/ESLint scoped, so doc changes don't enter the frontend gate.
- The hook can't run if the `app` container is down — it fails fast with a message; start it
  with `make up` or bypass with `SKIP_HOOKS=1`.

## Related

- [Services & stack](services-and-stack.md)
- [Backend conventions](../conventions/backend.md) · [Frontend conventions](../conventions/frontend.md)
- `CLAUDE.md` § "Workflow"
