import axios from 'axios';
import { Check, ChevronsUpDown, Loader2, Search } from 'lucide-react';
import { type UIEvent, useCallback, useEffect, useRef, useState } from 'react';

import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';
import { type SelectOption } from '@/types';

/** Cursor-paginated envelope returned by the *.options endpoints. */
interface OptionsPage {
    data: SelectOption[];
    next_cursor: string | null;
    has_more: boolean;
}

export interface AsyncSelectProps {
    /** Selected token, or undefined when none/cleared. */
    value: string | undefined;
    onChange: (value: string | undefined) => void;
    /** Ziggy route name of the options endpoint. Required unless `staticOptions` is given. */
    routeName?: string;
    /** Extra query params sent with every request (undefined/'' entries omitted), e.g. { organization }. */
    params?: Record<string, string | undefined>;
    /**
     * Render a fixed, already-loaded list instead of fetching — filtered
     * client-side. Use for small in-memory lists that still want the same
     * searchable picker UI (e.g. a Kanban board's milestones).
     */
    staticOptions?: SelectOption[];
    id?: string;
    placeholder?: string;
    /** Offer a "clear" entry that resets to undefined (filter / optional field). */
    allowClear?: boolean;
    /** Trigger/clear-entry text for the empty state (e.g. "All organizations"). */
    allLabel?: string;
    invalid?: boolean;
    disabled?: boolean;
    /** Trigger text when disabled (e.g. "Select an organization first"). */
    disabledHint?: string;
    dialogTitle?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    className?: string;
}

/** Drop undefined/empty params so they don't appear in the query string. */
function cleanParams(
    params?: Record<string, string | undefined>,
): Record<string, string> {
    const out: Record<string, string> = {};
    if (params) {
        for (const [key, val] of Object.entries(params)) {
            if (val !== undefined && val !== '') out[key] = val;
        }
    }
    return out;
}

/**
 * Async, search-as-you-type single-select. Fetches matches from a cursor-paginated
 * `*.options` endpoint on demand (debounced) and pages more rows in as the list
 * scrolls — so it scales to huge tables. Self-hydrates the label of a preselected
 * `value` via a one-shot token lookup. `params` scopes the query (e.g. by org for
 * cascading selects). Pass `staticOptions` instead to render a small in-memory
 * list with the same UI (no network). Built on the project's Dialog + list
 * pattern; <OrganizationSelect> and friends are thin wrappers over this.
 */
export default function AsyncSelect({
    value,
    onChange,
    routeName,
    params,
    staticOptions,
    id,
    placeholder = 'Select…',
    allowClear = false,
    allLabel = 'All',
    invalid = false,
    disabled = false,
    disabledHint,
    dialogTitle = 'Select an option',
    searchPlaceholder = 'Search…',
    emptyText = 'No results found.',
    className,
}: AsyncSelectProps) {
    const isStatic = staticOptions !== undefined;

    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [options, setOptions] = useState<SelectOption[]>([]);
    const [nextCursor, setNextCursor] = useState<string | null>(null);
    const [hasMore, setHasMore] = useState(false);
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    const [selected, setSelected] = useState<SelectOption | null>(null);
    const selectedRef = useRef<SelectOption | null>(null);
    selectedRef.current = selected;
    const timer = useRef<ReturnType<typeof setTimeout>>();

    // Stable string key so the param object's identity doesn't churn effects.
    const paramsKey = JSON.stringify(cleanParams(params));

    // Hydrate the selected label when `value` is set externally and unknown.
    useEffect(() => {
        if (isStatic) return;
        if (!value) {
            setSelected(null);
            return;
        }
        if (
            selectedRef.current &&
            String(selectedRef.current.value) === value
        ) {
            return;
        }
        let active = true;
        axios
            .get(route(routeName!), {
                params: { ...JSON.parse(paramsKey), tokens: [value] },
            })
            .then((r) => {
                if (active)
                    setSelected((r.data as OptionsPage).data[0] ?? null);
            })
            .catch(() => {});
        return () => {
            active = false;
        };
    }, [value, routeName, paramsKey, isStatic]);

    // Debounced first page on open / search / scope change.
    useEffect(() => {
        if (isStatic || !open) return;
        if (timer.current) clearTimeout(timer.current);
        setLoading(true);
        timer.current = setTimeout(() => {
            axios
                .get(route(routeName!), {
                    params: { ...JSON.parse(paramsKey), q: search },
                })
                .then((r) => {
                    const page = r.data as OptionsPage;
                    setOptions(page.data);
                    setNextCursor(page.next_cursor);
                    setHasMore(page.has_more);
                })
                .catch(() => {
                    setOptions([]);
                    setNextCursor(null);
                    setHasMore(false);
                })
                .finally(() => setLoading(false));
        }, 250);
        return () => {
            if (timer.current) clearTimeout(timer.current);
        };
    }, [open, search, routeName, paramsKey, isStatic]);

    const loadMore = useCallback(() => {
        if (isStatic || !hasMore || loadingMore || !nextCursor) return;
        setLoadingMore(true);
        axios
            .get(route(routeName!), {
                params: {
                    ...JSON.parse(paramsKey),
                    q: search,
                    cursor: nextCursor,
                },
            })
            .then((r) => {
                const page = r.data as OptionsPage;
                setOptions((prev) => [...prev, ...page.data]);
                setNextCursor(page.next_cursor);
                setHasMore(page.has_more);
            })
            .catch(() => {})
            .finally(() => setLoadingMore(false));
    }, [
        isStatic,
        hasMore,
        loadingMore,
        nextCursor,
        search,
        routeName,
        paramsKey,
    ]);

    const onScroll = (e: UIEvent<HTMLDivElement>) => {
        const el = e.currentTarget;
        if (el.scrollHeight - el.scrollTop - el.clientHeight < 64) {
            loadMore();
        }
    };

    const choose = (option: SelectOption) => {
        setSelected(option);
        onChange(String(option.value));
        setOpen(false);
    };

    const clear = () => {
        setSelected(null);
        onChange(undefined);
        setOpen(false);
    };

    // Static mode derives its list + selection from props (no fetch); async mode
    // reads the fetched state.
    const query = search.trim().toLowerCase();
    const displayed = isStatic
        ? query
            ? staticOptions!.filter((o) =>
                  o.label.toLowerCase().includes(query),
              )
            : staticOptions!
        : options;
    const selectedOption = isStatic
        ? value
            ? (staticOptions!.find((o) => String(o.value) === value) ?? null)
            : null
        : selected;

    const emptyLabel = allowClear ? allLabel : placeholder;
    const triggerLabel = selectedOption
        ? selectedOption.label
        : value
          ? isStatic
              ? emptyLabel
              : 'Loading…'
          : disabled && disabledHint
            ? disabledHint
            : emptyLabel;

    return (
        <>
            <Button
                type="button"
                variant="outline"
                id={id}
                disabled={disabled}
                onClick={() => {
                    setSearch('');
                    setOpen(true);
                }}
                className={cn(
                    'w-full justify-between font-normal',
                    invalid && 'border-destructive',
                    className,
                )}
            >
                <span
                    className={cn(
                        'truncate',
                        !selectedOption && 'text-muted-foreground',
                    )}
                >
                    {triggerLabel}
                </span>
                <ChevronsUpDown className="h-4 w-4 shrink-0 opacity-50" />
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{dialogTitle}</DialogTitle>
                    </DialogHeader>

                    <div className="relative">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="pl-8"
                            autoFocus
                        />
                    </div>

                    <div
                        className="max-h-72 space-y-1 overflow-y-auto"
                        onScroll={onScroll}
                    >
                        {allowClear && (
                            <button
                                type="button"
                                onClick={clear}
                                className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-muted"
                            >
                                <span className="flex-1 text-muted-foreground">
                                    {allLabel}
                                </span>
                                {!value && <Check className="h-4 w-4" />}
                            </button>
                        )}

                        {loading && displayed.length === 0 ? (
                            <p className="flex items-center justify-center gap-2 py-6 text-sm text-muted-foreground">
                                <Loader2 className="h-4 w-4 animate-spin" />
                                Searching…
                            </p>
                        ) : displayed.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                {emptyText}
                            </p>
                        ) : (
                            <>
                                {displayed.map((option) => {
                                    const v = String(option.value);
                                    return (
                                        <button
                                            key={v}
                                            type="button"
                                            onClick={() => choose(option)}
                                            className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-muted"
                                        >
                                            <span className="flex-1">
                                                {option.label}
                                            </span>
                                            {v === value && (
                                                <Check className="h-4 w-4" />
                                            )}
                                        </button>
                                    );
                                })}
                                {loadingMore && (
                                    <p className="flex items-center justify-center gap-2 py-3 text-sm text-muted-foreground">
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                        Loading more…
                                    </p>
                                )}
                            </>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
