import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Loader2, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
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
import { type AdminProject, type SelectOption } from '@/types';
import ProjectForm from './Partials/ProjectForm';

interface Props {
    // Serialized CursorPaginator wrapped by Inertia::scroll(); the
    // <InfiniteScroll> component appends pages into `data` as the user scrolls.
    projects: { data: AdminProject[] };
    filters: { search: string; inactive: boolean };
    organizations: SelectOption[];
}

export default function Index({ projects, filters, organizations }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'projects.index',
        reset: ['projects'],
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminProject | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formProject, setFormProject] = useState<AdminProject | null>(null);

    const openCreate = () => {
        setFormProject(null);
        setFormOpen(true);
    };

    const openEdit = (project: AdminProject) => {
        setFormProject(project);
        setFormOpen(true);
    };

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('projects.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Projects" />

            <PageHeader
                title="Projects"
                description="Projects grouped by organization."
                actions={
                    <Can ability="projects.create">
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" /> New Project
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
                </FilterBar>
            </div>

            {projects.data.length === 0 ? (
                <div className="rounded-lg border bg-card py-16 text-center text-sm text-muted-foreground">
                    No projects found.
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
                                <div className="space-y-1">
                                    <CardTitle className="flex items-center gap-2 text-base leading-tight">
                                        {/* Stretched link: the ::after overlay
                                            makes the whole card navigate to the
                                            show page, while the z-10 menu stays
                                            clickable above it. */}
                                        <Link
                                            href={route(
                                                'projects.show',
                                                project.token,
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
                                    <p className="text-sm text-muted-foreground">
                                        {project.organization_name ??
                                            'No organization'}
                                    </p>
                                </div>
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
                                                        openEdit(project)
                                                    }
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                            </Can>
                                            <Can ability="projects.delete">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setDeleting(project)
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

            <Sheet open={formOpen} onOpenChange={setFormOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>
                            {formProject
                                ? `Edit ${formProject.name}`
                                : 'New Project'}
                        </SheetTitle>
                        <SheetDescription>
                            A project belonging to an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <ProjectForm
                            key={formProject?.token ?? 'new'}
                            project={formProject ?? undefined}
                            organizations={organizations}
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
