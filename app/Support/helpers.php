<?php

use Illuminate\Contracts\Pagination\CursorPaginator;

if (! function_exists('cursorResponse')) {
    /**
     * Normalize a keyset (cursor) paginator into the shape the React <CursorPager>
     * consumes: { data, next_cursor, prev_cursor, has_more }.
     *
     * @param  (callable(mixed): mixed)|null  $map  optional per-item transform
     * @return array{data: array<int, mixed>, next_cursor: ?string, prev_cursor: ?string, has_more: bool}
     */
    function cursorResponse(CursorPaginator $paginator, ?callable $map = null): array
    {
        $items = $paginator->items();

        if ($map !== null) {
            $items = array_map($map, $items);
        }

        return [
            'data' => array_values($items),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'prev_cursor' => $paginator->previousCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
        ];
    }
}
