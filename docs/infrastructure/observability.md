# Observability — logs, metrics & traces

> Structured logs with a correlation id, the Pulse app-health dashboard, and where tracing goes next.

## Purpose

Production needs to answer three questions: *what happened* (logs), *how much / how fast* (metrics),
and *why a request was slow* (traces). This is the free, self-hosted foundation that fits the
"everything in Docker + MariaDB" model — structured logs tied together by a request id, plus a metrics
dashboard — and the layer worth keeping regardless of any future tracing backend. (Telescope is
**dev-only** debugging, a separate concern — see [services & stack](services-and-stack.md).)

## Key files

- `app/Http/Middleware/AssignRequestId.php` — stamps the correlation id (prepended globally in
  `bootstrap/app.php`).
- `config/logging.php` — the `stderr` channel used for JSON logs in production.
- `config/pulse.php` — Pulse recorders, sampling, and trim window.
- `app/Providers/AppServiceProvider.php` — the `viewPulse` gate (developer-only).
- `resources/views/vendor/pulse/dashboard.blade.php` — the published Pulse dashboard layout.

## How it works

### Logs + correlation
`AssignRequestId` honors an inbound `X-Request-Id` (e.g. from a proxy) or mints a UUID, then calls
`Context::add('request_id', $id)`. Laravel **automatically merges `Context` into every log record and
serializes it into queued jobs dispatched during the request** — so a controller log and the log from
a job it queued share the same `request_id`, with no per-call plumbing. The id is echoed back on the
`X-Request-Id` response header.

Dev keeps the readable `daily` file channel. **Production** should emit structured JSON to stdout
(12-factor — the container's stdout *is* the log stream): set `LOG_CHANNEL=stderr` and
`LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter` (documented in `.env.example`). Those JSON lines
carry `request_id` automatically; ship stdout to Loki/ELK/CloudWatch from there.

### Metrics — Laravel Pulse
`laravel/pulse` records into MariaDB and renders a dashboard at **`/pulse`**: slow queries, slow
requests, slow jobs, exceptions, cache hit rates, and usage (busiest users/routes). It's gated by the
`viewPulse` gate to the **developer** role (matching `Gate::before` god-mode). `PULSE_ENABLED` toggles
recording (forced `false` in `phpunit.xml`). Recorders run inline on normal requests/jobs — no daemon
needed.

## Decisions & why

- **Correlation via `Context`, not a Monolog processor** — Context is the one mechanism Laravel already
  propagates into both logs and queued jobs, so it covers the web request *and* its async work for free.
- **JSON logs only in prod** — local stays human-readable (`daily`); the `stderr` channel already
  exists, so the switch is env-only.
- **Pulse over an external metrics stack (for now)** — free, self-hosted, no new services, and it reads
  the same MariaDB the app already uses.
- **Telescope ≠ Pulse** — Telescope is local-only request inspection (dev); Pulse is the
  production-facing aggregate dashboard.

## Gotchas

- Server CPU/memory cards need the long-running `php artisan pulse:check` daemon (a future `pulse`
  compose service / supervised process); the query/request/job/exception cards don't.
- High-traffic deployments should switch Pulse to the **Redis ingest** driver so recording doesn't add
  write load to the app DB (`config/pulse.php`).
- Don't enable Telescope in production here — use Pulse + the JSON logs instead.

## Next: distributed tracing

The missing pillar is traces. When wanted, add either **OpenTelemetry** (the
`open-telemetry/opentelemetry-auto-laravel` instrumentation + the `opentelemetry` PECL extension in the
app image → OTLP → an OTel Collector → Tempo/Prometheus/Loki + Grafana, or a SaaS), or **Laravel
Nightwatch** (first-party, managed — the `NIGHTWATCH_ENABLED` flag is already scaffolded). Propagate the
existing `request_id` into the trace context so the three pillars line up.

## Related

- [Services & stack](services-and-stack.md) — Telescope (dev), queue/scheduler, logging channel.
- `CLAUDE.md` § "Debugging" / "User feedback"
