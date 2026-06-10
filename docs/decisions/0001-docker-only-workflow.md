# 0001 — Docker-only workflow

**Status:** accepted

## Context

Contributors run different host OSes and PHP/Node versions. "Works on my machine" drift —
mismatched PHP extensions, Node versions, missing `pg_dump` — is the usual cost.

## Decision

There is **no host PHP, Node, or Composer**. Every service runs in Docker
(`docker-compose.yml`) and every workflow is a one-word `Makefile` target wrapping
`docker compose`. Under the hood `APP := docker compose exec -T app` and
`NODE_RUN := docker compose run --rm node` (node is a dev-profile one-off, not a
long-running service).

## Consequences

- One command (`make setup`) bootstraps a working stack; `make help` lists everything.
- Tooling that needs system binaries is baked into the image — e.g. the app image
  installs the `postgresql-client` package (`pg_dump`/`psql`) so backups/restores work.
- The `node` container runs as **root**, so `npm run build` writes root-owned files under
  `public/build`; `make clean` removes generated/uploaded files + storage caches via a
  root container for this reason.
- Anything done outside the Makefile (e.g. a host editor's format-on-save) must still
  match the in-container tools — see [CI & hooks](../infrastructure/ci-and-hooks.md).

## Related

- [Services & stack](../infrastructure/services-and-stack.md)
- `CLAUDE.md` § "Everything runs in Docker"
