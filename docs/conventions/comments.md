# Commenting conventions

> Comment the *why*, not the *what*. When in doubt, delete it — or rename the
> code so it needs no comment.

## Purpose

Comments are a cost: they're unverified by the compiler, drift out of sync with
the code they describe, and add visual noise a reader has to skip. They earn
their place only when they say something the code itself cannot. This room is
the rule for when to keep one, when to delete one, and a record of the cleanup
that established the baseline.

This codebase deliberately carries a lot of **good** comments — browser/Inertia
quirks, security gotchas, ordering constraints, ADR links. That is correct and
should not be "tidied away". The goal is to delete *noise*, not insight.

## The rule

**Keep a comment when it explains something the code can't:**

- *Why* a thing is done this way — rationale, a trade-off, a deliberate
  non-obvious choice.
- A constraint or gotcha — "if you change X also change Y", ordering
  requirements, a platform/browser/framework quirk.
- A workaround and the reason it exists (so nobody "cleans it up" and
  reintroduces the bug).
- A pointer to deeper context — an ADR, a `docs/` room, an upstream issue.

**Delete a comment when it only restates the code:**

- It echoes the next line (`// loop through users` over a `foreach`).
- It repeats the symbol name (`/** Display the profile form. */` over
  `edit()`). These also go silently stale on a rename.
- It labels the obvious (`// constructor`, `// state for the modal`, a
  `{/* Footer */}` over a `<footer>`).
- It is **commented-out code**. Delete it — git remembers. Dead code masquerading
  as a comment is the worst offender.

### Good (keep)

From `resources/js/Components/Bell.tsx` — a CSS gotcha that stops the workaround
from being "simplified" back into a bug:

```tsx
// Close on click/tap outside or Escape. A backdrop div can't be used here
// because the parent nav's `backdrop-blur` makes `fixed` positioning
// resolve against the nav, not the viewport.
```

From `resources/js/Components/CursorPager.tsx` — a correctness invariant that is
invisible from the call itself:

```tsx
// Preserve existing query params (search, inactive, …) so the filtered result
// set the cursor was computed against stays the same. Sending only the cursor
// would re-run an unfiltered query and the keyset position would point into the
// wrong result set.
```

### Noise (delete)

JSX section labels that restate a `<section id>`/semantic tag — the worst
concentration was in `resources/js/Pages/Welcome.tsx`:

```tsx
{/* Header */}      // it's a <header>
{/* About */}       // the <section id="about"> already says so
{/* Footer */}      // it's a <footer>
```

Docblocks that just re-spell the method name (Breeze scaffolding leaves these):

```php
/** Display the user's profile form. */
public function edit(Request $request): Response
```

## Decisions & why

- **JSX section-label anti-pattern.** A long page tempts you to drop
  `{/* Hero */}`, `{/* Contact */}` markers. They feel like navigation but they
  duplicate the `id`/heading already on the element and rot when sections move.
  Prefer semantic structure (`<section id="contact">`, a heading) over a label
  comment. If a file is so long you crave section markers, that's a signal to
  split the component, not to comment it.
- **Magic-number comments → named constants.** If a comment exists only to
  explain a literal (`// capped at 30s`), the better fix is usually a named
  constant (`const WARN_WINDOW_CAP_S = 30`) the comment-free code then reads off
  — the value and its meaning can't drift apart. Don't just delete the insight.
- **Vendor-published files are exempt.** The spatie migration
  `database/migrations/*_create_permission_tables.php` ships with inline
  comments (`// permission id`) and a `docs/prerequisites.md` reference. Those
  are upstream stub text, kept verbatim so the file stays diffable against the
  package. Leave them; don't apply this convention to published vendor stubs.
- **Project quick-ref.** `CLAUDE.md` already states "prefer PHPDoc over inline
  comments; only add inline comments for exceptionally complex logic" and "be
  concise" — this room is the long-form of that rule.

## Gotchas

- A docblock that restates the signature isn't just noise — it's a *liability*:
  it survives renames and refactors and starts lying. Type hints + a good name
  beat a prose restatement.
- Don't strip a comment just because it's long. Length isn't the smell;
  redundancy is. The `use-idle-logout.ts` header comment is long and earns it.

## Cleanup baseline (2026-06-08)

Point-in-time inventory from the audit that established this convention. **Line
numbers drift** — treat this as a record, not a live index.

**Removed in this pass (high-confidence noise):**

- `resources/js/Pages/Welcome.tsx` — ~12 JSX section labels (`{/* Header */}`,
  `{/* Hero */}`, `{/* About */}`, `{/* Skills … */}`, `{/* Yin — backend */}`,
  `{/* Yang — frontend */}`, `{/* Experience */}`, `{/* Work */}`,
  `{/* Explore the backend … */}`, `{/* Contact */}`, `{/* Footer */}`,
  `{/* Screenshot lightbox */}`).
- `resources/js/Pages/Users/Index.tsx` — a ~14-line block of **commented-out**
  date-range filter inputs.
- `app/Http/Controllers/ProfileController.php` — three Breeze docblocks
  restating `edit` / `update` / `destroy`.
- `app/Http/Controllers/SettingsController.php` — `/** group => settings class */`
  over a self-evident `const GROUPS` map.

**Left in place (deliberate):**

- All *why* comments (a non-exhaustive sample: `use-idle-logout.ts`, `Bell.tsx`,
  `CursorPager.tsx`, `app.tsx`, the jobs and middleware).
- `app/Http/Controllers/MediaController.php` docblocks — borderline; they note
  ownership/authorization that isn't obvious from the signature. Tighten only if
  they start restating the code.
- The spatie permission migration (vendor stub, see above).

## Related

- [Backend conventions](backend.md), [Frontend conventions](frontend.md) — the
  resource shapes these comments live inside.
- `CLAUDE.md` § "Conventions when editing" — the dense quick-ref.
