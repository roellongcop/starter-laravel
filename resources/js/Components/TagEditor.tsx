import axios from 'axios';
import { Loader2, Plus, Search } from 'lucide-react';
import { type UIEvent, useCallback, useEffect, useRef, useState } from 'react';

import TagBadgesRow from '@/Components/TagBadgesRow';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { toast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';
import { type SelectOption, type TagChip } from '@/types';

/** Cursor-paginated envelope returned by data-tags.options. */
interface OptionsPage {
    data: SelectOption[];
    next_cursor: string | null;
    has_more: boolean;
}

interface Props {
    /** Current tags (with colours) shown as chips + the initial selection. */
    tags: TagChip[];
    /** Organization token that scopes the tag picker (required to edit). */
    organization: string | null;
    /** Taggable resource key for the generic endpoint, e.g. 'tasks', 'projects'. */
    type: string;
    /** The entity's token. */
    token: string;
    /** Show the add/edit affordance (otherwise read-only chips). */
    canEdit?: boolean;
    /**
     * Constrain the chips to a single row that fits as many as the container
     * width allows, collapsing the overflow into a "+N" (via <TagBadgesRow>) —
     * so a card's height is fixed regardless of tag count. Omit on detail pages
     * to let chips wrap across multiple rows.
     */
    singleRow?: boolean;
    /**
     * Optional hook fired after a successful save. The chips update themselves
     * from the response, so this is only for extra side-effects (e.g. refreshing
     * a tag count shown elsewhere on the page).
     */
    onSaved?: () => void;
    className?: string;
}

/**
 * Inline, reusable data-tag editor: renders a resource's tags as coloured chips
 * plus an "add/edit" affordance that opens an org-scoped tag picker, and persists
 * through the generic `taggables.sync` endpoint — so any taggable resource drops
 * it in with just its `type` + `token`. `relative z-10` keeps it clickable above
 * a card's stretched link.
 */
export default function TagEditor({
    tags,
    organization,
    type,
    token,
    canEdit = false,
    singleRow = false,
    onSaved,
    className,
}: Props) {
    const [open, setOpen] = useState(false);
    const [selected, setSelected] = useState<string[]>([]);
    const [saving, setSaving] = useState(false);
    const [search, setSearch] = useState('');
    const [options, setOptions] = useState<SelectOption[]>([]);
    const [nextCursor, setNextCursor] = useState<string | null>(null);
    const [hasMore, setHasMore] = useState(false);
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    const timer = useRef<ReturnType<typeof setTimeout>>();

    // Local copy so an inline save updates the chips immediately (no page
    // reload); re-synced when the entity's own tags change (navigation / reload).
    const [displayTags, setDisplayTags] = useState<TagChip[]>(tags);
    const tagsKey = tags.map((tag) => tag.token).join('|');
    const [syncedKey, setSyncedKey] = useState(tagsKey);
    if (tagsKey !== syncedKey) {
        setSyncedKey(tagsKey);
        setDisplayTags(tags);
    }

    // Debounced first page on open / search / scope change.
    useEffect(() => {
        if (!open || !organization) {
            return;
        }
        if (timer.current) {
            clearTimeout(timer.current);
        }
        setLoading(true);
        timer.current = setTimeout(() => {
            axios
                .get(route('data-tags.options'), {
                    params: { organization, q: search },
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
            if (timer.current) {
                clearTimeout(timer.current);
            }
        };
    }, [open, search, organization]);

    const loadMore = useCallback(() => {
        if (!hasMore || loadingMore || !nextCursor || !organization) {
            return;
        }
        setLoadingMore(true);
        axios
            .get(route('data-tags.options'), {
                params: { organization, q: search, cursor: nextCursor },
            })
            .then((r) => {
                const page = r.data as OptionsPage;
                setOptions((prev) => [...prev, ...page.data]);
                setNextCursor(page.next_cursor);
                setHasMore(page.has_more);
            })
            .catch(() => {})
            .finally(() => setLoadingMore(false));
    }, [hasMore, loadingMore, nextCursor, organization, search]);

    const onScroll = (e: UIEvent<HTMLDivElement>) => {
        const el = e.currentTarget;
        if (el.scrollHeight - el.scrollTop - el.clientHeight < 64) {
            loadMore();
        }
    };

    const startEditing = () => {
        setSelected(displayTags.map((tag) => tag.token));
        setSearch('');
        setOpen(true);
    };

    const toggle = (token: string) =>
        setSelected((prev) =>
            prev.includes(token)
                ? prev.filter((t) => t !== token)
                : [...prev, token],
        );

    // Persist on close when the selection changed from the current tags.
    const handleOpenChange = (nextOpen: boolean) => {
        if (nextOpen) {
            setOpen(true);

            return;
        }
        setOpen(false);

        const current = displayTags
            .map((tag) => tag.token)
            .sort()
            .join('|');
        const next = [...selected].sort().join('|');
        if (current === next) {
            return;
        }

        setSaving(true);
        axios
            .patch(route('taggables.sync', [type, token]), { tags: selected })
            .then((r) => {
                setDisplayTags((r.data as { tags: TagChip[] }).tags);
                onSaved?.();
            })
            .catch(() => {
                toast({
                    variant: 'destructive',
                    description: 'Could not update tags.',
                });
            })
            .finally(() => setSaving(false));
    };

    const chip = (tag: TagChip) => (
        <Badge
            key={tag.token}
            variant="outline"
            className="max-w-[12rem] gap-1.5 font-normal"
        >
            <span
                className="h-2 w-2 shrink-0 rounded-full"
                style={{ backgroundColor: tag.color }}
                aria-hidden
            />
            <span className="truncate">{tag.name}</span>
        </Badge>
    );

    const editChip = (
        <Button
            type="button"
            variant="outline"
            size="sm"
            className="h-6 shrink-0 gap-1 border-dashed px-2 text-xs text-muted-foreground"
            disabled={saving || !organization}
            title={organization ? undefined : 'No organization'}
            onClick={startEditing}
        >
            <Plus className="h-3 w-3" />
            {displayTags.length === 0 ? 'Add tags' : 'Edit'}
        </Button>
    );

    const noTags = (
        <span className="text-xs text-muted-foreground">No tags</span>
    );

    return (
        <div
            className={cn(
                'relative z-10 flex items-center gap-1.5',
                !singleRow && 'flex-wrap',
                className,
            )}
        >
            {singleRow ? (
                // One row that fits as many chips as the width allows, with the
                // edit chip hugging the tags right after the "+N" (via TagBadgesRow).
                canEdit ? (
                    <TagBadgesRow
                        tags={displayTags}
                        className="min-w-0 flex-1"
                        action={editChip}
                    />
                ) : displayTags.length > 0 ? (
                    <TagBadgesRow
                        tags={displayTags}
                        className="min-w-0 flex-1"
                    />
                ) : (
                    noTags
                )
            ) : (
                // Detail pages: chips wrap across rows, edit chip trails them.
                <>
                    {displayTags.map(chip)}
                    {canEdit ? editChip : displayTags.length === 0 && noTags}
                </>
            )}

            <Dialog open={open} onOpenChange={handleOpenChange}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Tags</DialogTitle>
                    </DialogHeader>

                    <div className="relative">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search tags…"
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
                                No tags for this organization.
                            </p>
                        ) : (
                            <>
                                {options.map((option) => {
                                    const token = String(option.value);

                                    return (
                                        <label
                                            key={token}
                                            className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted"
                                        >
                                            <Checkbox
                                                className="shrink-0"
                                                checked={selected.includes(
                                                    token,
                                                )}
                                                onCheckedChange={() =>
                                                    toggle(token)
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
                        <Button
                            type="button"
                            onClick={() => handleOpenChange(false)}
                        >
                            Done
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
