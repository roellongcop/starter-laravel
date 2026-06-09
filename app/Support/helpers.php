<?php

use Illuminate\Contracts\Pagination\CursorPaginator;

if (! function_exists('dated_path')) {
    /**
     * Prefix a storage filename with a YYYY/MM/ folder so generated artifacts
     * (backups/exports/imports) share the uploads layout. See
     * docs/infrastructure/services-and-stack.md.
     */
    function dated_path(string $name): string
    {
        return now()->format('Y/m').'/'.ltrim($name, '/');
    }
}

if (! function_exists('escape_like')) {
    /**
     * Escape LIKE metacharacters (\ % _) so user-supplied search input is
     * matched literally instead of as wildcards. The default '\' escape char
     * works on MySQL/MariaDB and SQLite. Used by the filter primitives and any
     * raw query-builder search (e.g. SessionController).
     */
    function escape_like(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}

if (! function_exists('cursorResponse')) {
    /**
     * Normalize a keyset paginator into the <CursorPager> envelope
     * ({ data, next_cursor, prev_cursor, has_more }). Pass $total to opt into an
     * exact count (omitted by default — no COUNT per page).
     * See docs/decisions/0002-keyset-cursor-pagination.md.
     *
     * @param  (callable(mixed): mixed)|null  $map  optional per-item transform
     * @return array{data: array<int, mixed>, next_cursor: ?string, prev_cursor: ?string, has_more: bool, total?: int}
     */
    function cursorResponse(CursorPaginator $paginator, ?callable $map = null, ?int $total = null): array
    {
        $items = $paginator->items();

        if ($map !== null) {
            $items = array_map($map, $items);
        }

        $response = [
            'data' => array_values($items),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'prev_cursor' => $paginator->previousCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
        ];

        if ($total !== null) {
            $response['total'] = $total;
        }

        return $response;
    }
}
