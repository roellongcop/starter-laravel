# 0002 — Keyset (cursor) pagination

**Status:** accepted

## Context

Offset pagination (`LIMIT … OFFSET …`) degrades on large tables and can skip or repeat
rows when data changes between page loads. The admin grids need stable, fast paging.

## Decision

Lists use **keyset (cursor) pagination only — no page numbers.** Controllers do:

```php
Model::query()->...->keyset()->cursorPaginate(config('keen.pagination_size'))
```

then wrap the result with the global `cursorResponse()` helper (`app/Support/helpers.php`)
→ `{ data, next_cursor, prev_cursor, has_more, total? }`, consumed by `<CursorPager>`. Pass
a 3rd arg to `cursorResponse()` to include an exact `total`.

## Consequences

- Every domain table carries an `index(['created_at', 'id'])` (added by the
  `auditColumns()` macro) so the keyset order is index-backed.
- The page size is `config('keen.pagination_size')`, which the System setting can override
  at runtime — see [0005](0005-settings-runtime-config-overrides.md).
- No "jump to page N"; navigation is Prev/Next on opaque cursors. Filters must be preserved
  across cursor navigation (see the pagination fix in git history).

## Related

- [Backend conventions](../conventions/backend.md)
- `CLAUDE.md` § "Keyset (cursor) pagination only"
