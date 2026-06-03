import { router } from '@inertiajs/react';
import axios from 'axios';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { type SearchGroup } from '@/types';

export default function GlobalSearch() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [groups, setGroups] = useState<SearchGroup[]>([]);
    const [loading, setLoading] = useState(false);
    const timer = useRef<ReturnType<typeof setTimeout>>();

    // Cmd/Ctrl-K opens the palette.
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                setOpen(true);
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    // Debounced search.
    useEffect(() => {
        if (timer.current) clearTimeout(timer.current);
        if (query.trim() === '') {
            setGroups([]);
            return;
        }
        setLoading(true);
        timer.current = setTimeout(() => {
            axios
                .get(route('dashboard.search'), { params: { q: query } })
                .then((r) => setGroups(r.data.groups ?? []))
                .catch(() => setGroups([]))
                .finally(() => setLoading(false));
        }, 250);
        return () => {
            if (timer.current) clearTimeout(timer.current);
        };
    }, [query]);

    const go = (href: string) => {
        setOpen(false);
        setQuery('');
        router.visit(href);
    };

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm text-muted-foreground hover:bg-accent"
            >
                <Search className="h-4 w-4" />
                <span className="hidden sm:inline">Search…</span>
                <kbd className="hidden rounded bg-muted px-1.5 text-xs sm:inline">
                    ⌘K
                </kbd>
            </button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="top-24 translate-y-0">
                    <DialogHeader>
                        <DialogTitle>Search</DialogTitle>
                    </DialogHeader>
                    <Input
                        autoFocus
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search users, roles, files, IPs…"
                    />
                    <div className="max-h-80 overflow-auto">
                        {loading && (
                            <p className="px-1 py-3 text-sm text-muted-foreground">
                                Searching…
                            </p>
                        )}
                        {!loading &&
                            query.trim() !== '' &&
                            groups.length === 0 && (
                                <p className="px-1 py-3 text-sm text-muted-foreground">
                                    No results.
                                </p>
                            )}
                        {groups.map((group) => (
                            <div key={group.label} className="py-2">
                                <p className="px-1 pb-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    {group.label}
                                </p>
                                {group.hits.map((hit) => (
                                    <button
                                        key={hit.href}
                                        type="button"
                                        onClick={() => go(hit.href)}
                                        className="flex w-full flex-col rounded-md px-2 py-1.5 text-left hover:bg-accent"
                                    >
                                        <span className="text-sm">
                                            {hit.label}
                                        </span>
                                        {hit.sublabel && (
                                            <span className="text-xs text-muted-foreground">
                                                {hit.sublabel}
                                            </span>
                                        )}
                                    </button>
                                ))}
                            </div>
                        ))}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
