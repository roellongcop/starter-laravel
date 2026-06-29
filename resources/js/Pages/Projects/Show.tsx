import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { Loader2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import StatusBadge from '@/Components/StatusBadge';
import StatusDropdown from '@/Components/StatusDropdown';
import TagBadges from '@/Components/TagBadges';
import TagBadgesRow from '@/Components/TagBadgesRow';
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
    type AdminProject,
    type Crumb,
    type DataTagOption,
    type ProjectAsset,
    type SelectOption,
} from '@/types';
import ManageProjectAssets from './Partials/ManageProjectAssets';
import ProjectForm from './Partials/ProjectForm';

interface Props {
    project: AdminProject;
    organizations: SelectOption[];
    dataTags: DataTagOption[];
    // Inertia::scroll() cursor paginator; <InfiniteScroll> appends pages into `data`.
    projectAssets: { data: ProjectAsset[] };
    assetsTotal: number;
    filters: { search: string };
    assetOptions: SelectOption[];
    selectedAssetTokens: string[];
    statusOptions: SelectOption[];
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
    statusOptions,
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
                    <CardContent className="p-4">
                        <dl className="grid grid-cols-[6.5rem_1fr] items-center gap-x-4 gap-y-2.5 text-sm">
                            <dt className="text-muted-foreground">
                                Organization
                            </dt>
                            <dd className="truncate">
                                {project.organization_name || '—'}
                            </dd>

                            <dt className="text-muted-foreground">
                                Visibility
                            </dt>
                            <dd>
                                <Badge
                                    variant={
                                        project.private
                                            ? 'secondary'
                                            : 'outline'
                                    }
                                >
                                    {project.private ? 'Private' : 'Public'}
                                </Badge>
                            </dd>

                            <dt className="text-muted-foreground">Status</dt>
                            <dd>
                                <Can
                                    ability="projects.update"
                                    fallback={
                                        <StatusBadge status={project.status} />
                                    }
                                >
                                    <StatusDropdown
                                        value={project.status}
                                        options={statusOptions}
                                        onSelect={(status) =>
                                            axios.patch(
                                                route(
                                                    'projects.status',
                                                    project.token,
                                                ),
                                                { status },
                                            )
                                        }
                                    />
                                </Can>
                            </dd>

                            <dt className="self-start text-muted-foreground">
                                Description
                            </dt>
                            <dd className="whitespace-pre-line">
                                {project.description || '—'}
                            </dd>

                            <dt className="self-start text-muted-foreground">
                                Tags
                            </dt>
                            <dd>
                                {project.tags.length > 0 ? (
                                    <TagBadges tags={project.tags} />
                                ) : (
                                    <span className="text-muted-foreground">
                                        —
                                    </span>
                                )}
                            </dd>
                        </dl>
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
                                        className="grid gap-3 xl:grid-cols-2"
                                        loading={
                                            <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                                                <Loader2 className="h-5 w-5 animate-spin" />
                                            </div>
                                        }
                                    >
                                        {projectAssets.data.map((asset) => (
                                            <Card
                                                key={asset.token}
                                                className="relative flex min-h-[4.75rem] items-stretch overflow-hidden transition-shadow hover:shadow-md"
                                            >
                                                {/* Status as a leading, fully-clickable "addon" cell */}
                                                <div className="flex items-stretch border-r bg-muted/30">
                                                    <Can
                                                        ability="projects.update"
                                                        fallback={
                                                            <span className="flex items-center px-3">
                                                                <StatusBadge
                                                                    status={
                                                                        asset.status
                                                                    }
                                                                />
                                                            </span>
                                                        }
                                                    >
                                                        <StatusDropdown
                                                            iconOnly
                                                            variant="ghost"
                                                            className="h-full w-auto rounded-none px-3"
                                                            value={asset.status}
                                                            options={
                                                                statusOptions
                                                            }
                                                            onSelect={(
                                                                status,
                                                            ) =>
                                                                axios.patch(
                                                                    route(
                                                                        'projects.assets.status',
                                                                        [
                                                                            project.token,
                                                                            asset.token,
                                                                        ],
                                                                    ),
                                                                    { status },
                                                                )
                                                            }
                                                        />
                                                    </Can>
                                                </div>

                                                <div className="min-w-0 flex-1 space-y-1 p-3">
                                                    <Link
                                                        href={route(
                                                            'projects.assets.show',
                                                            [
                                                                project.token,
                                                                asset.token,
                                                            ],
                                                        )}
                                                        className="block truncate font-medium after:absolute after:inset-0 focus-visible:outline-none"
                                                    >
                                                        {asset.name}
                                                    </Link>
                                                    <p className="truncate text-xs text-muted-foreground">
                                                        <span className="font-mono">
                                                            {asset.id_code}
                                                        </span>
                                                        {asset.address
                                                            ? ` · ${asset.address}`
                                                            : ''}
                                                    </p>
                                                    <TagBadgesRow
                                                        tags={asset.tags}
                                                    />
                                                </div>
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
