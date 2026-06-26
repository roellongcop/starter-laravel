import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import CursorPager from '@/Components/CursorPager';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminTheme, type CursorResponse } from '@/types';

interface Props {
    themes: CursorResponse<AdminTheme>;
    filters: { search: string };
}

export default function Index({ themes, filters }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'themes.index',
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminTheme | null>(null);

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('themes.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Themes" />

            <PageHeader
                title="Themes"
                description="Light/dark token palettes. The default theme restyles the whole app."
                actions={
                    <Can ability="themes.create">
                        <Button asChild>
                            <Link href={route('themes.create')}>
                                <Plus className="h-4 w-4" /> New Theme
                            </Link>
                        </Button>
                    </Can>
                }
            />

            <FilterBar onSubmit={f.submit} className="mb-4">
                <FilterBar.Search
                    value={f.values.search}
                    onChange={(v) => f.set('search', v)}
                    placeholder="Search themes…"
                />
            </FilterBar>

            <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Default</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {themes.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="text-center text-muted-foreground"
                                >
                                    No themes found.
                                </TableCell>
                            </TableRow>
                        )}
                        {themes.data.map((theme) => (
                            <TableRow key={theme.token}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={route('themes.show', theme.token)}
                                        className="hover:underline"
                                    >
                                        {theme.name}
                                    </Link>
                                </TableCell>
                                <TableCell
                                    className="max-w-sm truncate text-sm text-muted-foreground"
                                    title={theme.description ?? undefined}
                                >
                                    {theme.description || '—'}
                                </TableCell>
                                <TableCell>
                                    {theme.is_default && <Badge>Default</Badge>}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-1">
                                        <Can ability="themes.update">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Edit"
                                                aria-label="Edit"
                                                asChild
                                            >
                                                <Link
                                                    href={route(
                                                        'themes.edit',
                                                        theme.token,
                                                    )}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </Can>
                                        {!theme.is_default && (
                                            <Can ability="themes.delete">
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    title="Delete"
                                                    aria-label="Delete"
                                                    onClick={() =>
                                                        setDeleting(theme)
                                                    }
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            </Can>
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <CursorPager
                    nextCursor={themes.next_cursor}
                    prevCursor={themes.prev_cursor}
                />
            </div>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
                title={`Delete ${deleting?.name}?`}
                description="This permanently removes the theme."
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
