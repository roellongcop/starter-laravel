import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Loader2, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import OrganizationSelect from '@/Components/OrganizationSelect';
import PageHeader from '@/Components/PageHeader';
import TagBadgesRow from '@/Components/TagBadgesRow';
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
import { type AdminAsset } from '@/types';
import AssetForm from './Partials/AssetForm';

interface Props {
    // Serialized CursorPaginator wrapped by Inertia::scroll(); the
    // <InfiniteScroll> component appends pages into `data` as the user scrolls.
    assets: { data: AdminAsset[] };
    filters: { search: string; organization: string; inactive: boolean };
}

export default function Index({ assets, filters }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'assets.index',
        reset: ['assets'],
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminAsset | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formAsset, setFormAsset] = useState<AdminAsset | null>(null);

    const openCreate = () => {
        setFormAsset(null);
        setFormOpen(true);
    };

    const openEdit = (asset: AdminAsset) => {
        setFormAsset(asset);
        setFormOpen(true);
    };

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('assets.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Assets" />

            <PageHeader
                title="Assets"
                description="Assets grouped by organization."
                actions={
                    <Can ability="assets.create">
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" /> New Asset
                        </Button>
                    </Can>
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <FilterBar onSubmit={f.submit}>
                    <FilterBar.Search
                        value={f.values.search}
                        onChange={(v) => f.set('search', v)}
                        placeholder="Search name, code or address…"
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

            {assets.data.length === 0 ? (
                <div className="rounded-lg border bg-card py-16 text-center text-sm text-muted-foreground">
                    No assets found.
                </div>
            ) : (
                <InfiniteScroll
                    data="assets"
                    buffer={300}
                    className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    loading={
                        <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin" />
                        </div>
                    }
                >
                    {assets.data.map((asset) => (
                        <Card
                            key={asset.token}
                            className="relative flex h-full flex-col transition-all hover:border-ring hover:shadow-md"
                        >
                            <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                <div className="min-w-0 space-y-1">
                                    <CardTitle className="flex min-w-0 items-center gap-2 text-base leading-tight">
                                        {/* Stretched link: the ::after overlay
                                            makes the whole card navigate to the
                                            show page, while the z-10 menu stays
                                            clickable above it. */}
                                        <Link
                                            href={route(
                                                'assets.show',
                                                asset.token,
                                            )}
                                            className="line-clamp-1 after:absolute after:inset-0 focus-visible:outline-none"
                                        >
                                            {asset.name}
                                        </Link>
                                    </CardTitle>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {asset.organization_name ??
                                            'No organization'}
                                    </p>
                                </div>
                                <Can anyOf={['assets.update', 'assets.delete']}>
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
                                            <Can ability="assets.update">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        openEdit(asset)
                                                    }
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                            </Can>
                                            <Can ability="assets.delete">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setDeleting(asset)
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
                            <CardContent className="flex flex-1 flex-col gap-2">
                                <p className="font-mono text-xs text-muted-foreground">
                                    {asset.id_code}
                                </p>
                                <p className="line-clamp-2 min-h-10 text-sm text-muted-foreground">
                                    {asset.address || '—'}
                                </p>
                                <div className="mt-auto pt-2">
                                    <TagBadgesRow tags={asset.tags} />
                                </div>
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
                            {formAsset ? `Edit ${formAsset.name}` : 'New Asset'}
                        </SheetTitle>
                        <SheetDescription>
                            An asset belonging to an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <AssetForm
                            key={formAsset?.token ?? 'new'}
                            asset={formAsset ?? undefined}
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
