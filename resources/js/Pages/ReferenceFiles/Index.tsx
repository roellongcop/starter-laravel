import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import {
    Download,
    Loader2,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import TagBadges from '@/Components/TagBadges';
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
import {
    type AdminReferenceFile,
    type DataTagOption,
    type SelectOption,
} from '@/types';
import ReferenceFileForm from './Partials/ReferenceFileForm';

interface Props {
    references: { data: AdminReferenceFile[] };
    filters: { search: string; organization: string; inactive: boolean };
    organizations: SelectOption[];
    dataTags: DataTagOption[];
}

export default function Index({
    references,
    filters,
    organizations,
    dataTags,
}: Props) {
    const f = useFilters<Props['filters']>({
        route: 'reference-files.index',
        reset: ['references'],
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminReferenceFile | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formReference, setFormReference] =
        useState<AdminReferenceFile | null>(null);

    const openCreate = () => {
        setFormReference(null);
        setFormOpen(true);
    };

    const openEdit = (reference: AdminReferenceFile) => {
        setFormReference(reference);
        setFormOpen(true);
    };

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('reference-files.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Reference Files" />

            <PageHeader
                title="Reference Files"
                description="Reference documents grouped by organization."
                actions={
                    <Can ability="reference-files.create">
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" /> New Reference
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
                    <FilterBar.Select
                        value={f.values.organization}
                        onChange={(v) => f.apply({ organization: v })}
                        options={organizations}
                        placeholder="All organizations"
                        allLabel="All organizations"
                        className="w-56"
                    />
                </FilterBar>
            </div>

            {references.data.length === 0 ? (
                <div className="rounded-lg border bg-card py-16 text-center text-sm text-muted-foreground">
                    No references found.
                </div>
            ) : (
                <InfiniteScroll
                    data="references"
                    buffer={300}
                    className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    loading={
                        <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin" />
                        </div>
                    }
                >
                    {references.data.map((reference) => (
                        <Card
                            key={reference.token}
                            className="relative flex flex-col transition-shadow hover:shadow-md"
                        >
                            <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                <div className="space-y-1">
                                    <CardTitle className="flex items-center gap-2 text-base leading-tight">
                                        <Link
                                            href={route(
                                                'reference-files.show',
                                                reference.token,
                                            )}
                                            className="after:absolute after:inset-0 focus-visible:outline-none"
                                        >
                                            {reference.name}
                                        </Link>
                                    </CardTitle>
                                    <p className="text-sm text-muted-foreground">
                                        {reference.organization_name ??
                                            'No organization'}
                                    </p>
                                </div>
                                <Can
                                    anyOf={[
                                        'reference-files.update',
                                        'reference-files.delete',
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
                                            <Can ability="reference-files.update">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        openEdit(reference)
                                                    }
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                            </Can>
                                            <Can ability="reference-files.delete">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setDeleting(reference)
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
                            <CardContent className="space-y-2">
                                <p className="line-clamp-2 text-sm text-muted-foreground">
                                    {reference.description || '—'}
                                </p>
                                {reference.file_url && (
                                    <a
                                        href={reference.file_url}
                                        className="relative z-10 inline-flex items-center gap-1.5 text-sm text-primary hover:underline"
                                    >
                                        <Download className="h-4 w-4" />
                                        {reference.file_name ?? 'Download'}
                                    </a>
                                )}
                                <TagBadges tags={reference.tags} />
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
                            {formReference
                                ? `Edit ${formReference.name}`
                                : 'New Reference'}
                        </SheetTitle>
                        <SheetDescription>
                            A reference with an optional attached file.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <ReferenceFileForm
                            key={formReference?.token ?? 'new'}
                            reference={formReference ?? undefined}
                            organizations={organizations}
                            dataTags={dataTags}
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
