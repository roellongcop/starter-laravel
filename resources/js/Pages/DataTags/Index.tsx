import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Loader2, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import OrganizationSelect from '@/Components/OrganizationSelect';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
import { useFilters } from '@/hooks/use-filters';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminDataTag } from '@/types';
import DataTagForm from './Partials/DataTagForm';

interface Props {
    dataTags: { data: AdminDataTag[] };
    filters: { search: string; organization: string; inactive: boolean };
    colors: string[];
}

export default function Index({ dataTags, filters, colors }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'data-tags.index',
        reset: ['dataTags'],
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminDataTag | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formTag, setFormTag] = useState<AdminDataTag | null>(null);

    const openCreate = () => {
        setFormTag(null);
        setFormOpen(true);
    };

    const openEdit = (tag: AdminDataTag) => {
        setFormTag(tag);
        setFormOpen(true);
    };

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('data-tags.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Data Tags" />

            <PageHeader
                title="Data Tags"
                description="Coloured tags grouped by organization."
                actions={
                    <Can ability="data-tags.create">
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" /> New Tag
                        </Button>
                    </Can>
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <FilterBar onSubmit={f.submit}>
                    <FilterBar.Search
                        value={f.values.search}
                        onChange={(v) => f.set('search', v)}
                        placeholder="Search name or description…"
                    />
                    <OrganizationSelect
                        value={f.values.organization || undefined}
                        onChange={(v) => f.apply({ organization: v })}
                        allowClear
                        allLabel="All organizations"
                        className="w-56"
                    />
                </FilterBar>
            </div>

            {dataTags.data.length === 0 ? (
                <div className="rounded-lg border bg-card py-16 text-center text-sm text-muted-foreground">
                    No tags found.
                </div>
            ) : (
                <InfiniteScroll
                    data="dataTags"
                    buffer={300}
                    className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    loading={
                        <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin" />
                        </div>
                    }
                >
                    {dataTags.data.map((tag) => (
                        <Card
                            key={tag.token}
                            className="relative flex h-full flex-col transition-all hover:border-ring hover:shadow-md"
                        >
                            <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                <div className="min-w-0 space-y-1">
                                    <CardTitle className="flex min-w-0 items-center gap-2 text-base leading-tight">
                                        <span
                                            className="h-3 w-3 shrink-0 rounded-full"
                                            style={{
                                                backgroundColor: tag.color,
                                            }}
                                            aria-hidden
                                        />
                                        <Link
                                            href={route(
                                                'data-tags.show',
                                                tag.token,
                                            )}
                                            className="line-clamp-1 after:absolute after:inset-0 focus-visible:outline-none"
                                        >
                                            {tag.name}
                                        </Link>
                                    </CardTitle>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {tag.organization_name ??
                                            'No organization'}
                                    </p>
                                </div>
                                <Can
                                    anyOf={[
                                        'data-tags.update',
                                        'data-tags.delete',
                                    ]}
                                >
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                className="relative z-10 shrink-0"
                                                aria-label="Actions"
                                            >
                                                <MoreHorizontal className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <Can ability="data-tags.update">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        openEdit(tag)
                                                    }
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                            </Can>
                                            <Can ability="data-tags.delete">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setDeleting(tag)
                                                    }
                                                    className="text-destructive focus:text-destructive"
                                                >
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </Can>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </Can>
                            </CardHeader>
                            <CardContent className="flex flex-1 flex-col">
                                <p className="line-clamp-2 min-h-10 text-sm text-muted-foreground">
                                    {tag.description || '—'}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </InfiniteScroll>
            )}

            <Sheet open={formOpen} onOpenChange={setFormOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>
                            {formTag ? `Edit ${formTag.name}` : 'New Tag'}
                        </SheetTitle>
                        <SheetDescription>
                            A coloured tag belonging to an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <DataTagForm
                            key={formTag?.token ?? 'new'}
                            dataTag={formTag ?? undefined}
                            colors={colors}
                            onSuccess={() => setFormOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
                title={`Delete ${deleting?.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
