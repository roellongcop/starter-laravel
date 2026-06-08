# Mobile API auth (Sanctum bearer tokens)

## Purpose

A **stateless** authentication entry point for a future mobile app (or any third-party
client), sitting alongside the stateful session/cookie auth that powers the Inertia web UI.
The web app and the API share the same `User`s and credentials; they differ only in *how* a
request proves who it is.

| Surface | Auth mechanism | Stateful? |
| --- | --- | --- |
| `routes/web.php` (Inertia/React) | session cookie + CSRF | yes |
| `routes/api.php` (`/api/v1/*`) | Sanctum personal-access token (`Authorization: Bearer …`) | no |

## Key files

- `routes/api.php` — the versioned (`v1`) route group; `login`/`register` are public, the rest sit behind `auth:sanctum`.
- `app/Http/Controllers/Api/V1/AuthController.php` — `register` / `login` / `me` / `logout` / `logoutAll`.
- `app/Http/Requests/Api/V1/LoginRequest.php` — credential validation + login throttle.
- `app/Http/Requests/Api/V1/RegisterRequest.php` — signup validation (mirrors web Breeze rules).
- `app/Models/User.php` — uses `Laravel\Sanctum\HasApiTokens`.
- `config/sanctum.php` — `'expiration' => null` (tokens never expire).

## How it works

### `POST /api/v1/register` (public)

Self-signup. Mirrors the web Breeze flow — same validation, assigns the read-only
`user` role, fires the `Registered` event — then returns a token (`201`) instead of starting a
session.

```json
{ "name": "Ada", "email": "ada@example.com", "password": "Sup3r-Secret!pw", "password_confirmation": "Sup3r-Secret!pw", "device_name": "pixel-8" }
```

`password` must satisfy `Password::defaults()` and be confirmed via `password_confirmation`;
`email` must be unique. Response shape is identical to login (below).

### `POST /api/v1/login` (public)

Request:

```json
{ "email": "developer@developer.com", "password": "developer@developer.com", "device_name": "pixel-8" }
```

`device_name` is optional (defaults to `"mobile"`) and becomes the token's label so a user can
later tell their devices apart. Response:

```json
{
  "token": "12|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "token_type": "Bearer",
  "user": { "id": 1, "name": "Dev", "email": "developer@developer.com", "roles": ["developer"], "permissions": ["users.update", "…"] }
}
```

Store the `token` on the device. `roles`/`permissions` let the client gate its UI the same way
the web frontend does via the shared Inertia `auth` props.

### Authenticated requests

Send the token on every subsequent call:

```
Authorization: Bearer 12|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json
```

- `GET  /api/v1/me` → the current user payload.
- `POST /api/v1/logout` → revokes **only the current token** (signs out this one device).
- `POST /api/v1/logout-all` → revokes **every** token for the user (sign out everywhere).

A missing/invalid/revoked token yields `401 Unauthorized`. Validation failures (bad email,
wrong password, throttled) yield `422` with Laravel's standard `{ "message", "errors": { "email": [...] } }`.

## Decisions & why

- **Stateless token, not `Auth::attempt()`.** `LoginRequest::authenticate()` verifies the
  password with `Hash::check` and returns the `User` without touching the session, so the API
  never depends on cookies/CSRF.
- **Tokens never expire** (`'expiration' => null`) — mobile users stay signed in until they log
  out. Flip this with `SANCTUM_EXPIRATION` (minutes) if a TTL is wanted later.
- **Full-ability tokens (`['*']`).** Per-request authorization is still enforced by the existing
  spatie policies/permissions, so scoping abilities onto the token would only duplicate that.
- **Login throttling is reused, not reinvented** — the same email|ip 5-attempt limiter as the web
  `Auth\LoginRequest`.
- **Inactive users can't log in** — the `User` global `active` scope (`record_status`) means the
  credential lookup never finds an inactive account.

## Gotchas

- `api.php` is registered in `bootstrap/app.php` and is **not** in the `web` middleware group, so
  there's no session, CSRF, or IP-rules middleware on these routes — that's intentional for a
  stateless API.
- Always send `Accept: application/json` so Laravel returns JSON (422/401) instead of a redirect.
- Adding a `v2` later: add `->prefix('v2')` + an `Api\V2` controller; keep `v1` intact.

## Related

- [Users, roles & permissions](users-roles-permissions.md) — the authorization model these tokens still obey.
- [0003 — record_status, not soft deletes](../decisions/0003-record-status-not-soft-deletes.md) — why inactive users can't authenticate.
