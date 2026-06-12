# SEO & server-side rendering

> How public pages get crawlable HTML + meta tags, while the admin app stays a CSR SPA.

## Purpose

The app is a client-rendered Inertia SPA, so `<Head>` tags are injected by JavaScript and the
initial HTML carries no title/meta/content. Social and link crawlers (Facebook, LinkedIn, Slack,
X, iMessage) don't run JS, so previews break and indexing is unreliable.

The fix is **selective SSR**: the public/SEO pages (`/`, `/contact`, and future DB-driven pages)
are server-side rendered so they ship fully-rendered HTML + meta; the authenticated admin app
stays plain CSR (it's never crawled, and SSR-rendering its large component surface would add cost
and SSR-safety constraints for no benefit).

## Key files

- `config/inertia.php` — `ssr.enabled` defaults **false** (opt-in); `ssr.url` defaults to the
  same-host Inertia port and is overridden per environment.
- `app/Http/Middleware/EnableSsr.php` — flips `inertia.ssr.enabled` true for one route group.
- `routes/web.php` — the `EnableSsr` group wrapping the public/SEO routes.
- `resources/js/ssr.tsx` — the Node SSR entry; `resources/js/app.tsx` — conditional hydration.
- `app/Http/Middleware/HandleInertiaRequests.php` — shares the Ziggy config (SSR responses only).
- `app/Support/Seo.php` + `config/seo.php` — the SEO value object and its defaults.
- `resources/js/Components/Seo.tsx` — renders the `seo` prop into `<Head>`.
- `app/Http/Controllers/SitemapController.php` — `/sitemap.xml`; `/robots.txt` route in `web.php`.
- `docker-compose.yml` / `docker-compose.prod.yml` — the `ssr` Node service on `:13714`.

## How it works

**Per-request SSR.** Inertia reads `config('inertia.ssr.enabled')` when rendering, so it can be
toggled per request. `EnableSsr` sets it true on the public route group; everything else falls
back to CSR. Default-off means a forgotten route degrades to CSR rather than a broken SSR render.

**The Node renderer.** `npm run build` runs `vite build && vite build --ssr`, emitting
`bootstrap/ssr/ssr.js`. The `ssr` Docker service runs it on `:13714`; the app dispatches to it via
`INERTIA_SSR_URL`. If the bundle is missing or the service is down, Inertia falls back to CSR
(`inertia.ssr.ensure_bundle_exists`). `ssr.tsx` mirrors `app.tsx`'s render tree
(`ThemeProvider → App → Toaster`) so the client hydrates the markup it produces; `app.tsx` calls
`hydrateRoot` when the root has server-rendered children, else `createRoot`.

**Ziggy in Node.** App code calls the global `route()`, which on the client comes from the
`@routes` Blade directive — that never runs in Node. So `ssr.tsx` exposes the Ziggy config (shared
as the `ziggy` prop) on `globalThis` for `route()` to read. The vite `ziggy-js` alias points at the
vendored composer package, so no npm dependency is added.

**SEO data.** Controllers build an `App\Support\Seo` (`Seo::make(...)`, or `Seo::fromModel(...)`
for a model with `meta_title`/`meta_description`/`og_image`) and pass it as the `seo` prop;
`<Seo>` renders title, description, canonical, Open Graph, Twitter, and JSON-LD into `<Head>`,
which lands in the SSR HTML via `@inertiaHead`. Defaults (site name, OG image = brand logo, locale,
twitter handle) come from `config/seo.php`.

**Sitemap & robots.** `/sitemap.xml` lists the public URLs; `/robots.txt` is a route (not a static
file) so its `Sitemap:` line uses the live `APP_URL`.

## Decisions & why

- **Selective SSR, not all-SSR or meta-tags-only.** SSR on the public pages puts both content and
  meta in the HTML the idiomatic way; scoping it to public routes keeps the admin app's
  browser-only components out of the Node renderer. (See `ssr-public-pages-only` in the build memory.)
- **`ziggy` shared lazily.** The web-group `HandleInertiaRequests::share()` runs *before* the
  route-level `EnableSsr`, so a synchronous `if (ssr.enabled)` would always be false. The `ziggy`
  prop is a closure that reads the config at resolution time (after `EnableSsr`), returning `null`
  on CSR responses to keep the route list off every admin payload.
- **`ssr.tsx` omits `bootstrap.ts`.** That module's top-level `window.axios = …` would throw in
  Node; the SSR entry only needs the render wrapper.
- **No static `<title>` in `app.blade.php`.** With SSR, `@inertiaHead` emits the title; a static
  `<title inertia>` would produce a duplicate in the raw HTML (crawlers pick the wrong one).
- **Dynamic `robots.txt`.** A static file can't carry an absolute, environment-correct `Sitemap:`
  URL; nginx's `location = /robots.txt` falls through to `index.php`.

## Gotchas

- **Public pages must be SSR-safe.** Any render-time access to `window`/`document`/`localStorage`/
  `matchMedia` in a public page (or its imports) must be guarded with `typeof window !== 'undefined'`
  or moved into an effect, or it crashes the Node renderer and hydration mismatches. `useReveal`
  starts hidden on both server and first client render for this reason.
- **Rebuild the SSR bundle on deploy** and restart the `ssr` service, or it serves stale code.
  Verify with `php artisan inertia:check-ssr`.
- **Adding a public/SEO page:** put its route in the `EnableSsr` group, pass a `seo` prop, render
  `<Seo>`, and add it to `SitemapController`.

## Related

- [Deployment](../infrastructure/deployment.md) — running the `ssr` service in production.
- [Frontend conventions](../conventions/frontend.md) — React/TS, Inertia patterns.
- `CLAUDE.md` § "Stack" / "Architecture" — the quick-ref.
