# Theming

> CSS-variable light/dark themes, applied via a `data-theme` attribute with no flash.

> **TODO** — stub. Fill in when next touching this area, following [`TEMPLATE.md`](../TEMPLATE.md).

## Purpose

_TODO._

## Key files

- `resources/js/Components/ThemeProvider.tsx` — theme state + applies `data-theme` to `<html>`.
- `resources/css/app.css` — CSS variable palettes.
- `resources/views/app.blade.php` — no-flash inline script (applies persisted theme pre-paint).
- `app/Http/Controllers/ThemeController.php` — theme CRUD (token palettes + color picker).

## How it works

_TODO — `default_theme` setting → shared prop → `ThemeProvider` initial state; admin-managed
theme token palettes; live application._

## Decisions & why

_TODO — CSS variables + `data-theme` (not class toggling); see ADR 0005 for the
`default_theme` setting wiring._

## Gotchas

_TODO._

## Related

- [Settings](settings.md) · [Frontend conventions](../conventions/frontend.md)
- `CLAUDE.md` § Theming / README § Theming
