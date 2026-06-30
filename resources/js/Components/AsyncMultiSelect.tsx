import axios from 'axios';
import { ChevronsUpDown, Loader2, Search, X } from 'lucide-react';
import { type UIEvent, useCallback, useEffect, useRef, useState } from 'react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
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

interface Props {
    /** Selected tokens. */
    values: string[];
    onChange: (values: string[]) => void;
    /** Ziggy route name of the options endpoint, e.g. 'data-tags.options'. */
    routeName: string;
    /** Extra query params sent with every request (undefined/'' entries omitted), e.g. { organization }. */
    params?: Record<string, string | undefined>;
    id?: string;
    placeholder?: string;
    title?: string;
    description?: string;
    emptyText?: string;
    searchPlaceholder?: string;
    disabled?: boolean;
    /** Trigger text when disabled (e.g. "Select an organization first"). */
    disabledHint?: string;
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
 * Async, search-as-you-type multi-select. The multi-value sibling of
 * <AsyncSelect>: fetches matches from a cursor-paginated `*.options` endpoint on
 * demand, pages more in on scroll, and self-hydrates the labels of preselected
 * tokens so chips render on edit. `params` scopes the query (e.g. by org). Built
 * on the project's Dialog + checkbox-list pattern, mirroring <MultiSelect>.
 */
export default function AsyncMultiSelect({
    values,
    onChange,
    routeName,
    params,
    id,
    placeholder = 'Select…',
    title = 'Select options',
    description,
    emptyText = 'No results found.',
    searchPlaceholder = 'Search…',
    disabled = false,
    disabledHint,
    className,
}: Props) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [options, setOptions] = useState<SelectOption[]>([]);
    const [nextCursor, setNextCursor] = useState<string | null>(null);
    const [hasMore, setHasMore] = useState(false);
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    // token -> label, accumulated from hydration + fetched pages + toggles, so
    // selected chips always render a name.
    const [known, setKnown] = useState<Record<string, string>>({});
    const knownRef = useRef(known);
    knownRef.current = known;
    const timer = useRef<ReturnType<typeof setTimeout>>();

    const paramsKey = JSON.stringify(cleanParams(params));
    const valuesKey = values.join('|');

    const mergeKnown = useCallback((opts: SelectOption[]) => {
        if (opts.length === 0) return;
        setKnown((prev) => {
            const next = { ...prev };
            opts.forEach((o) => {
                next[String(o.value)] = o.label;
            });
            return next;
        });
    }, []);

    // Hydrate labels for selected tokens we don't yet know (edit forms).
    useEffect(() => {
        const selectedTokens = valuesKey ? valuesKey.split('|') : [];
        const missing = selectedTokens.filter((v) => !(v in knownRef.current));
        if (missing.length === 0) return;
        let active = true;
        axios
            .get(route(routeName), {
                params: { ...JSON.parse(paramsKey), tokens: missing },
            })
            .then((r) => {
                if (active) mergeKnown((r.data as OptionsPage).data);
            })
            .catch(() => {});
        return () => {
            active = false;
        };
    }, [valuesKey, routeName, paramsKey, mergeKnown]);

    // Debounced first page on open / search / scope change.
    useEffect(() => {
        if (!open) return;
        if (timer.current) clearTimeout(timer.current);
        setLoading(true);
        timer.current = setTimeout(() => {
            axios
                .get(route(routeName), {
                    params: { ...JSON.parse(paramsKey), q: search },
                })
                .then((r) => {
                    const page = r.data as OptionsPage;
                    setOptions(page.data);
                    setNextCursor(page.next_cursor);
                    setHasMore(page.has_more);
                    mergeKnown(page.data);
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
    }, [open, search, routeName, paramsKey, mergeKnown]);

    const loadMore = useCallback(() => {
        if (!hasMore || loadingMore || !nextCursor) return;
        setLoadingMore(true);
        axios
            .get(route(routeName), {
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
                mergeKnown(page.data);
            })
            .catch(() => {})
            .finally(() => setLoadingMore(false));
    }, [
        hasMore,
        loadingMore,
        nextCursor,
        search,
        routeName,
        paramsKey,
        mergeKnown,
    ]);

    const onScroll = (e: UIEvent<HTMLDivElement>) => {
        const el = e.currentTarget;
        if (el.scrollHeight - el.scrollTop - el.clientHeight < 64) {
            loadMore();
        }
    };

    const toggle = (option: SelectOption) => {
        const v = String(option.value);
        mergeKnown([option]);
        if (values.includes(v)) {
            onChange(values.filter((x) => x !== v));
        } else {
            onChange([...values, v]);
        }
    };

    const remove = (v: string) => onChange(values.filter((x) => x !== v));

    return (
        <div className={className}>
            <Button
                type="button"
                variant="outline"
                id={id}
                disabled={disabled}
                onClick={() => {
                    setSearch('');
                    setOpen(true);
                }}
                className="w-full justify-between font-normal"
            >
                <span
                    className={cn(
                        'min-w-0 flex-1 truncate text-left',
                        values.length === 0 && 'text-muted-foreground',
                    )}
                >
                    {values.length > 0
                        ? `${values.length} selected`
                        : disabled && disabledHint
                          ? disabledHint
                          : placeholder}
                </span>
                <ChevronsUpDown className="h-4 w-4 shrink-0 opacity-50" />
            </Button>

            {values.length > 0 && (
                <div className="mt-2 flex flex-wrap gap-1.5">
                    {values.map((v) => (
                        <Badge
                            key={v}
                            variant="secondary"
                            className="max-w-full gap-1"
                        >
                            <span className="truncate">{known[v] ?? v}</span>
                            <button
                                type="button"
                                onClick={() => remove(v)}
                                className="shrink-0 rounded-full outline-none ring-offset-background hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"
                                aria-label={`Remove ${known[v] ?? v}`}
                            >
                                <X className="h-3 w-3" />
                            </button>
                        </Badge>
                    ))}
                </div>
            )}

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        {description && (
                            <DialogDescription>{description}</DialogDescription>
                        )}
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
                        {loading && options.length === 0 ? (
                            <p className="flex items-center justify-center gap-2 py-6 text-sm text-muted-foreground">
                                <Loader2 className="h-4 w-4 animate-spin" />
                                Searching…
                            </p>
                        ) : options.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                {emptyText}
                            </p>
                        ) : (
                            <>
                                {options.map((option) => {
                                    const v = String(option.value);
                                    return (
                                        <label
                                            key={v}
                                            className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted"
                                        >
                                            <Checkbox
                                                className="shrink-0"
                                                checked={values.includes(v)}
                                                onCheckedChange={() =>
                                                    toggle(option)
                                                }
                                            />
                                            <span className="min-w-0 flex-1 break-words">
                                                {option.label}
                                            </span>
                                        </label>
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

                    <DialogFooter>
                        <Button type="button" onClick={() => setOpen(false)}>
                            Done
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
