<?php

use Illuminate\Contracts\Pagination\CursorPaginator;

if (! function_exists('dated_path')) {
    /**
     * Prefix a storage filename with a YYYY/MM/ folder so generated artifacts
     * (backups, exports, imports) follow the same date-foldered layout as
     * uploads (see App\Support\MediaPathGenerator). Keeps disks tidy instead of
     * dumping everything at the root.
     */
    function dated_path(string $name): string
    {
        return now()->format('Y/m').'/'.ltrim($name, '/');
    }
}

if (! function_exists('cursorResponse')) {
    /**
     * Normalize a keyset (cursor) paginator into the shape the React <CursorPager>
     * consumes: { data, next_cursor, prev_cursor, has_more }.
     *
     * Pass $total to include an exact result count in the envelope. Keyset
     * pagination deliberately omits it (no COUNT per page), so only opt in for
     * lists where the count is cheap or genuinely useful (e.g. filtered views).
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
