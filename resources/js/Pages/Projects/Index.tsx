import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Loader2, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
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

type BulkProcess = 'active' | 'in_active' | 'delete';

export default function Index({ projects, filters, organizations }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'projects.index',
        reset: ['projects'],
        initial: filters,
    });
    const [selected, setSelected] = useState<string[]>([]);
    const [bulk, setBulk] = useState<BulkProcess | null>(null);
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

    const toggleRow = (id: string) =>
        setSelected((s) =>
            s.includes(id) ? s.filter((x) => x !== id) : [...s, id],
        );

    const runBulk = () => {
        if (!bulk) return;
        router.post(
            route('projects.bulk'),
            { process: bulk, tokens: selected },
            {
                preserveScroll: true,
                onFinish: () => {
                    setBulk(null);
                    setSelected([]);
                },
            },
        );
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

                {selected.length > 0 && (
                    <div className="ml-auto flex items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                            {selected.length} selected
                        </span>
                        <Can ability="projects.update">
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setBulk('in_active')}
                            >
                                Inactivate
                            </Button>
                        </Can>
                        <Can ability="projects.delete">
                            <Button
                                size="sm"
                                variant="destructive"
                                onClick={() => setBulk('delete')}
                            >
                                Delete
                            </Button>
                        </Can>
                    </div>
                )}
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
                    {projects.data.map((project) => {
                        const isSelected = selected.includes(project.token);
                        return (
                            <Card
                                key={project.token}
                                data-selected={isSelected}
                                className="relative flex flex-col transition-shadow focus-within:ring-2 focus-within:ring-ring hover:shadow-md data-[selected=true]:ring-2 data-[selected=true]:ring-primary"
                            >
                                <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                    <div className="flex items-start gap-3">
                                        <Checkbox
                                            className="relative z-10 mt-1"
                                            checked={isSelected}
                                            onCheckedChange={() =>
                                                toggleRow(project.token)
                                            }
                                            aria-label={`Select ${project.name}`}
                                        />
                                        <div className="space-y-1">
                                            <CardTitle className="flex items-center gap-2 text-base leading-tight">
                                                {/* Stretched link: the ::after
                                                    overlay makes the whole card
                                                    navigate to the show page,
                                                    while z-10 controls stay
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
                                    </div>
                                    <div className="relative z-10 flex shrink-0 gap-1">
                                        <Can ability="projects.update">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Edit"
                                                aria-label="Edit"
                                                onClick={() =>
                                                    openEdit(project)
                                                }
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                        </Can>
                                        <Can ability="projects.delete">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Delete"
                                                aria-label="Delete"
                                                onClick={() =>
                                                    setDeleting(project)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </Can>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="line-clamp-3 text-sm text-muted-foreground">
                                        {project.description ?? '—'}
                                    </p>
                                </CardContent>
                            </Card>
                        );
                    })}
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
                open={bulk !== null}
                onOpenChange={(o) => !o && setBulk(null)}
                title={`Apply "${bulk}" to ${selected.length} project(s)?`}
                confirmLabel="Apply"
                destructive={bulk === 'delete'}
                onConfirm={runBulk}
            />

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
