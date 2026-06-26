import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import TagBadges from '@/Components/TagBadges';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
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
    type AdminAsset,
    type AdminProject,
    type Crumb,
    type DataTagOption,
    type SelectOption,
} from '@/types';
import ManageProjectAssets from './Partials/ManageProjectAssets';
import ProjectForm from './Partials/ProjectForm';

interface Props {
    project: AdminProject;
    organizations: SelectOption[];
    dataTags: DataTagOption[];
    // Inertia::scroll() cursor paginator; <InfiniteScroll> appends pages into `data`.
    projectAssets: { data: AdminAsset[] };
    assetsTotal: number;
    filters: { search: string };
    assetOptions: SelectOption[];
    selectedAssetTokens: string[];
}

export default function Show({
    project,
    organizations,
    dataTags,
    projectAssets,
    assetsTotal,
    filters,
    assetOptions,
    selectedAssetTokens,
}: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const [manageOpen, setManageOpen] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    // Server-side search over the project's bound assets; resets the scroll prop
    // so a re-filter replaces the accumulated rows instead of appending.
    const assetFilters = useFilters<Props['filters']>({
        route: 'projects.show',
        params: project.token,
        reset: ['projectAssets'],
        initial: filters,
    });

    const destroy = () =>
        router.delete(route('projects.destroy', project.token), {
            onFinish: () => setConfirmingDelete(false),
        });

    const breadcrumbs: Crumb[] = [
        { label: 'Projects', href: route('projects.index') },
        { label: project.name },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={project.name} />

            <PageHeader
                title={project.name}
                breadcrumbs={breadcrumbs}
                actions={
                    <>
                        <Can ability="projects.update">
                            <Button onClick={() => setEditOpen(true)}>
                                Edit
                            </Button>
                        </Can>
                        <Can ability="projects.delete">
                            <Button
                                variant="destructive"
                                onClick={() => setConfirmingDelete(true)}
                            >
                                Delete
                            </Button>
                        </Can>
                    </>
                }
            />

            <div className="space-y-6">
                <Card>
                    <CardContent className="space-y-4 pt-6">
                        <div>
                            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                Organization
                            </span>
                            <p className="mt-1 text-sm">
                                {project.organization_name || '—'}
                            </p>
                        </div>
                        <div>
                            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                Visibility
                            </span>
                            <div className="mt-1">
                                <Badge
                                    variant={
                                        project.private
                                            ? 'secondary'
                                            : 'outline'
                                    }
                                >
                                    {project.private ? 'Private' : 'Public'}
                                </Badge>
                            </div>
                        </div>
                        <div>
                            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                Description
                            </span>
                            <p className="mt-1 text-sm">
                                {project.description || '—'}
                            </p>
                        </div>
                        <div>
                            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                Tags
                            </span>
                            {project.tags.length > 0 ? (
                                <TagBadges
                                    tags={project.tags}
                                    className="mt-1"
                                />
                            ) : (
                                <p className="mt-1 text-sm text-muted-foreground">
                                    No tags.
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-base">
                            Assets
                            {assetsTotal > 0 && (
                                <span className="ml-1 font-normal text-muted-foreground">
                                    ({assetsTotal})
                                </span>
                            )}
                        </CardTitle>
                        <Can ability="projects.update">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setManageOpen(true)}
                            >
                                Manage assets
                            </Button>
                        </Can>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {assetsTotal === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No assets bound to this project yet.
                            </p>
                        ) : (
                            <>
                                <FilterBar onSubmit={assetFilters.submit}>
                                    <FilterBar.Search
                                        value={assetFilters.values.search}
                                        onChange={(v) =>
                                            assetFilters.set('search', v)
                                        }
                                        placeholder="Search name, code or address…"
                                    />
                                </FilterBar>

                                {projectAssets.data.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No assets match your search.
                                    </p>
                                ) : (
                                    <InfiniteScroll
                                        data="projectAssets"
                                        buffer={300}
                                        className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                                        loading={
                                            <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                                                <Loader2 className="h-5 w-5 animate-spin" />
                                            </div>
                                        }
                                    >
                                        {projectAssets.data.map((asset) => (
                                            <Card
                                                key={asset.token}
                                                className="relative flex flex-col transition-shadow hover:shadow-md"
                                            >
                                                <CardHeader className="space-y-1">
                                                    <CardTitle className="text-base leading-tight">
                                                        {/* Stretched link makes the
                                                            whole card open the asset. */}
                                                        <Link
                                                            href={route(
                                                                'assets.show',
                                                                asset.token,
                                                            )}
                                                            className="after:absolute after:inset-0 focus-visible:outline-none"
                                                        >
                                                            {asset.name}
                                                        </Link>
                                                    </CardTitle>
                                                    <p className="text-sm text-muted-foreground">
                                                        {asset.organization_name ??
                                                            'No organization'}
                                                    </p>
                                                </CardHeader>
                                                <CardContent className="space-y-2">
                                                    <p className="font-mono text-xs text-muted-foreground">
                                                        {asset.id_code}
                                                    </p>
                                                    <p className="line-clamp-2 text-sm text-muted-foreground">
                                                        {asset.address || '—'}
                                                    </p>
                                                    <TagBadges
                                                        tags={asset.tags}
                                                    />
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </InfiniteScroll>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Sheet open={editOpen} onOpenChange={setEditOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>{`Edit ${project.name}`}</SheetTitle>
                        <SheetDescription>
                            A project belonging to an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <ProjectForm
                            project={project}
                            organizations={organizations}
                            dataTags={dataTags}
                            onSuccess={() => setEditOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <Sheet open={manageOpen} onOpenChange={setManageOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>Manage assets</SheetTitle>
                        <SheetDescription>
                            Bind existing organization assets to this project.
                            Unselecting an asset only detaches it — the asset
                            itself is never deleted.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <ManageProjectAssets
                            project={project}
                            selected={selectedAssetTokens}
                            assetOptions={assetOptions}
                            onSuccess={() => setManageOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <ConfirmDialog
                open={confirmingDelete}
                onOpenChange={setConfirmingDelete}
                title={`Delete ${project.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
