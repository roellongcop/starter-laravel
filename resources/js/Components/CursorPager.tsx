import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import { Button } from '@/Components/ui/button';

interface CursorPagerProps {
    nextCursor: string | null;
    prevCursor: string | null;
    /**
     * Query-string key the backend reads the cursor from. Defaults to "cursor"
     * (Laravel's cursorPaginate default).
     */
    cursorName?: string;
    /** Partial-reload prop keys to fetch on navigation (Inertia `only`). */
    only?: string[];
    className?: string;
}

/**
 * Keyset pagination control: Prev/Next only — no page numbers, no sortable
 * headers (lists are ordered created_at DESC, id DESC server-side). Navigates by
 * pushing the opaque cursor onto the current URL via Inertia.
 */
export default function CursorPager({
    nextCursor,
    prevCursor,
    cursorName = 'cursor',
    only,
    className,
}: CursorPagerProps) {
    const go = (cursor: string | null) => {
        if (!cursor) return;
        // Preserve existing query params (search, inactive, etc.) so the
        // filtered result set the cursor was computed against stays the same.
        // Sending only the cursor would re-run an unfiltered query and the
        // keyset position would point into the wrong result set.
        const params = Object.fromEntries(
            new URLSearchParams(window.location.search),
        );
        params[cursorName] = cursor;
        router.get(window.location.pathname, params, {
            preserveState: true,
            preserveScroll: true,
            ...(only ? { only } : {}),
        });
    };

    return (
        <nav
            className={`flex items-center justify-end gap-2 ${className ?? ''}`}
            aria-label="Pagination"
        >
            <Button
                variant="outline"
                size="sm"
                disabled={!prevCursor}
                onClick={() => go(prevCursor)}
            >
                <ChevronLeft className="h-4 w-4" />
                Previous
            </Button>
            <Button
                variant="outline"
                size="sm"
                disabled={!nextCursor}
                onClick={() => go(nextCursor)}
            >
                Next
                <ChevronRight className="h-4 w-4" />
            </Button>
        </nav>
    );
}
