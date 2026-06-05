# Theming

> CSS-variable light/dark themes, applied via a `data-theme` attribute with no flash.

## Purpose

The whole app is styled from a small set of CSS variables. Light/dark is a `data-theme` attribute on
`<html>`, and an admin-managed `Theme` can override the variable values app-wide by injecting a
`<style>` block — so re-skinning is data, not a rebuild.

## Key files

- `resources/js/Components/ThemeProvider.tsx` — light/dark/system state; persists to `localStorage`
  and applies `data-theme` to `<html>`.
- `resources/js/Components/ThemeStyle.tsx` — injects the active `Theme`'s `tokens` as CSS variables.
- `resources/js/Components/ThemeToggle.tsx` — the light/dark switch.
- `resources/css/app.css` — the static default palettes (the `--background`, `--primary`, … variables).
- `resources/views/app.blade.php` — the no-flash inline script that applies the persisted theme
  pre-paint.
- `app/Http/Controllers/ThemeController.php` + `resources/js/Pages/Themes/*` — theme CRUD with a token
  palette / color picker.

## How it works

- **Light/dark.** `ThemeProvider` holds `'light' | 'dark' | 'system'`, resolves `system` against
  `prefers-color-scheme`, and writes `data-theme="light|dark"` onto `<html>`. Tailwind's
  `[data-theme="dark"]` selector and the variables in `app.css` key off that attribute. The choice
  persists in `localStorage` (`keen-admin-theme`); the inline script in `app.blade.php` applies it
  before first paint so there's no flash.
- **Custom palettes.** A `Theme` row stores a `tokens` JSON of `{ light: {…}, dark: {…} }` CSS-variable
  values. `ThemeStyle` reads the active theme from the shared Inertia prop and emits a `<style>` block
  setting those variables on `:root` (light) and `[data-theme="dark"]` (dark), **overriding** the
  static `app.css` defaults — that's how one Theme restyles the entire app live.
- **Default.** The `default_theme` SystemSetting flows through to `ThemeProvider`'s initial state via
  the shared `settings.system` prop.

## Decisions & why

- **CSS variables + `data-theme`, not class toggling** — one attribute flips the whole palette, and
  variable overrides compose cleanly with Tailwind.
- **The default theme is a runtime setting**, not a constant — see
  [ADR 0005 — settings as runtime config](../decisions/0005-settings-runtime-config-overrides.md).

## Gotchas

- `tokens` keys must match the variable names in `app.css` (e.g. `--primary`); an unknown key is
  injected but styles nothing.
- The seeded "Keen" palette mirrors `app.css`, so the seeded default changes nothing visually until
  edited.

## Related

- [Settings](settings.md) · [Frontend conventions](../conventions/frontend.md)
- `CLAUDE.md` § Theming
