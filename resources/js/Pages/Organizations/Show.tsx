import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Loader2, MoreHorizontal, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
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
import AssetForm from '@/Pages/Assets/Partials/AssetForm';
import ProjectForm from '@/Pages/Projects/Partials/ProjectForm';
import { type AdminOrganization, type SelectOption } from '@/types';
import OrganizationForm from './Partials/OrganizationForm';

interface OrganizationProject {
    token: string;
    name: string;
    private: boolean;
    description: string | null;
}

interface OrganizationAsset {
    token: string;
    name: string;
    id_code: string;
    address: string;
}

interface Props {
    organization: AdminOrganization;
    // Keyset-paginated via Inertia::scroll(); <InfiniteScroll> appends pages.
    projects: { data: OrganizationProject[] };
    projectsTotal: number;
    projectFilters: { search: string };
    // Second independent <InfiniteScroll> list, with its own search param.
    assets: { data: OrganizationAsset[] };
    assetsTotal: number;
    assetFilters: { asset_search: string };
    users: SelectOption[];
    organizationOptions: SelectOption[];
}

export default function Show({
    organization,
    projects,
    projectsTotal,
    projectFilters,
    assets,
    assetsTotal,
    assetFilters,
    users,
    organizationOptions,
}: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [projectFormOpen, setProjectFormOpen] = useState(false);
    const [editingProject, setEditingProject] =
        useState<OrganizationProject | null>(null);
    const [deletingProject, setDeletingProject] =
        useState<OrganizationProject | null>(null);
    const [assetFormOpen, setAssetFormOpen] = useState(false);
    const [editingAsset, setEditingAsset] = useState<OrganizationAsset | null>(
        null,
    );
    const [deletingAsset, setDeletingAsset] =
        useState<OrganizationAsset | null>(null);

    // One filter state drives both search boxes: a single navigation carries
    // both params so neither list clobbers the other's term out of the URL.
    const f = useFilters<{ search: string; asset_search: string }>({
        route: 'organizations.show',
        params: organization.token,
        reset: ['projects', 'assets'],
        initial: {
            search: projectFilters.search,
            asset_search: assetFilters.asset_search,
        },
    });

    const destroy = () =>
        router.delete(route('organizations.destroy', organization.token), {
            onFinish: () => setConfirmingDelete(false),
        });

    const openEditProject = (project: OrganizationProject) => {
        setEditingProject(project);
        setProjectFormOpen(true);
    };

    const destroyProject = () => {
        if (!deletingProject) return;
        // Nested route → redirects back to this org page (not projects index).
        router.delete(
            route('organizations.projects.destroy', [
                organization.token,
                deletingProject.token,
            ]),
            {
                preserveScroll: true,
                onFinish: () => setDeletingProject(null),
            },
        );
    };

    const openEditAsset = (asset: OrganizationAsset) => {
        setEditingAsset(asset);
        setAssetFormOpen(true);
    };

    const destroyAsset = () => {
        if (!deletingAsset) return;
        // Nested route → redirects back to this org page (not assets index).
        router.delete(
            route('organizations.assets.destroy', [
                organization.token,
                deletingAsset.token,
            ]),
            {
                preserveScroll: true,
                onFinish: () => setDeletingAsset(null),
            },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title={organization.name} />

            <PageHeader
                title={organization.name}
                breadcrumbs={[
                    {
                        label: 'Organizations',
                        href: route('organizations.index'),
                    },
                    { label: organization.name },
                ]}
                actions={
                    <>
                        <Can ability="organizations.update">
                            <Button onClick={() => setEditOpen(true)}>
                                Edit
                            </Button>
                        </Can>
                        <Can ability="organizations.delete">
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

            <Card>
                <CardContent className="space-y-4 pt-6">
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Point of contact
                        </span>
                        <p className="mt-1 text-sm">
                            {organization.point_of_contact_name || '—'}
                        </p>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Description
                        </span>
                        <p className="mt-1 text-sm">
                            {organization.description || '—'}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Can ability="projects.index">
                <div className="mt-6">
                    <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold tracking-tight">
                            Projects
                            <span className="ml-2 text-sm font-normal text-muted-foreground">
                                {projectsTotal}
                            </span>
                        </h2>
                        <FilterBar onSubmit={f.submit}>
                            <FilterBar.Search
                                value={f.values.search}
                                onChange={(v) => f.set('search', v)}
                                placeholder="Search projects…"
                            />
                        </FilterBar>
                    </div>
                    {projects.data.length === 0 ? (
                        <div className="rounded-lg border bg-card py-10 text-center text-sm text-muted-foreground">
                            {projectFilters.search
                                ? 'No projects match your search.'
                                : 'No projects yet.'}
                        </div>
                    ) : (
                        <InfiniteScroll
                            data="projects"
                            buffer={300}
                            className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                            loading={
                                <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                                    <Loader2 className="h-5 w-5 animate-spin" />
                                </div>
                            }
                        >
                            {projects.data.map((project) => (
                                <Card
                                    key={project.token}
                                    className="relative flex flex-col transition-shadow hover:shadow-md"
                                >
                                    <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                        <CardTitle className="flex items-center gap-2 text-base leading-tight">
                                            <Link
                                                href={route(
                                                    'organizations.projects.show',
                                                    [
                                                        organization.token,
                                                        project.token,
                                                    ],
                                                )}
                                                className="after:absolute after:inset-0 hover:underline focus-visible:outline-none"
                                            >
                                                {project.name}
                                            </Link>
                                            {project.private && (
                                                <Badge variant="secondary">
                                                    Private
                                                </Badge>
                                            )}
                                        </CardTitle>
                                        <Can
                                            anyOf={[
                                                'projects.update',
                                                'projects.delete',
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
                                                    <Can ability="projects.update">
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                openEditProject(
                                                                    project,
                                                                )
                                                            }
                                                        >
                                                            <Pencil className="mr-2 h-4 w-4" />
                                                            Edit
                                                        </DropdownMenuItem>
                                                    </Can>
                                                    <Can ability="projects.delete">
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                setDeletingProject(
                                                                    project,
                                                                )
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
                                    <CardContent>
                                        <p className="line-clamp-3 text-sm text-muted-foreground">
                                            {project.description ?? '—'}
                                        </p>
                                    </CardContent>
                                </Card>
                            ))}
                        </InfiniteScroll>
                    )}
                </div>
            </Can>

            <Can ability="assets.index">
                <div className="mt-6">
                    <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold tracking-tight">
                            Assets
                            <span className="ml-2 text-sm font-normal text-muted-foreground">
                                {assetsTotal}
                            </span>
                        </h2>
                        <FilterBar onSubmit={f.submit}>
                            <FilterBar.Search
                                value={f.values.asset_search}
                                onChange={(v) => f.set('asset_search', v)}
                                placeholder="Search assets…"
                            />
                        </FilterBar>
                    </div>
                    {assets.data.length === 0 ? (
                        <div className="rounded-lg border bg-card py-10 text-center text-sm text-muted-foreground">
                            {assetFilters.asset_search
                                ? 'No assets match your search.'
                                : 'No assets yet.'}
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
                                    className="relative flex flex-col transition-shadow hover:shadow-md"
                                >
                                    <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                        <CardTitle className="text-base leading-tight">
                                            <Link
                                                href={route(
                                                    'organizations.assets.show',
                                                    [
                                                        organization.token,
                                                        asset.token,
                                                    ],
                                                )}
                                                className="after:absolute after:inset-0 hover:underline focus-visible:outline-none"
                                            >
                                                {asset.name}
                                            </Link>
                                        </CardTitle>
                                        <Can
                                            anyOf={[
                                                'assets.update',
                                                'assets.delete',
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
                                                    <Can ability="assets.update">
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                openEditAsset(
                                                                    asset,
                                                                )
                                                            }
                                                        >
                                                            <Pencil className="mr-2 h-4 w-4" />
                                                            Edit
                                                        </DropdownMenuItem>
                                                    </Can>
                                                    <Can ability="assets.delete">
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                setDeletingAsset(
                                                                    asset,
                                                                )
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
                                    <CardContent className="space-y-1">
                                        <p className="font-mono text-xs text-muted-foreground">
                                            {asset.id_code}
                                        </p>
                                        <p className="line-clamp-2 text-sm text-muted-foreground">
                                            {asset.address || '—'}
                                        </p>
                                    </CardContent>
                                </Card>
                            ))}
                        </InfiniteScroll>
                    )}
                </div>
            </Can>

            <Sheet open={editOpen} onOpenChange={setEditOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>{`Edit ${organization.name}`}</SheetTitle>
                        <SheetDescription>
                            An organization and its point of contact.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <OrganizationForm
                            organization={organization}
                            users={users}
                            onSuccess={() => setEditOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <Sheet open={projectFormOpen} onOpenChange={setProjectFormOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>
                            {editingProject
                                ? `Edit ${editingProject.name}`
                                : 'Project'}
                        </SheetTitle>
                        <SheetDescription>
                            A project belonging to an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        {editingProject && (
                            <ProjectForm
                                key={editingProject.token}
                                project={{
                                    token: editingProject.token,
                                    name: editingProject.name,
                                    description: editingProject.description,
                                    private: editingProject.private,
                                    organization: organization.token,
                                }}
                                organizations={organizationOptions}
                                onSuccess={() => setProjectFormOpen(false)}
                            />
                        )}
                    </div>
                </SheetContent>
            </Sheet>

            <Sheet open={assetFormOpen} onOpenChange={setAssetFormOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>
                            {editingAsset
                                ? `Edit ${editingAsset.name}`
                                : 'Asset'}
                        </SheetTitle>
                        <SheetDescription>
                            An asset belonging to an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        {editingAsset && (
                            <AssetForm
                                key={editingAsset.token}
                                asset={{
                                    token: editingAsset.token,
                                    name: editingAsset.name,
                                    id_code: editingAsset.id_code,
                                    address: editingAsset.address,
                                    organization: organization.token,
                                }}
                                organizations={organizationOptions}
                                onSuccess={() => setAssetFormOpen(false)}
                            />
                        )}
                    </div>
                </SheetContent>
            </Sheet>

            <ConfirmDialog
                open={confirmingDelete}
                onOpenChange={setConfirmingDelete}
                title={`Delete ${organization.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />

            <ConfirmDialog
                open={deletingProject !== null}
                onOpenChange={(o) => !o && setDeletingProject(null)}
                title={`Delete ${deletingProject?.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroyProject}
            />

            <ConfirmDialog
                open={deletingAsset !== null}
                onOpenChange={(o) => !o && setDeletingAsset(null)}
                title={`Delete ${deletingAsset?.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroyAsset}
            />
        </AuthenticatedLayout>
    );
}
