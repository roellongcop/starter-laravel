import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { Loader2, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import OrganizationSelect from '@/Components/OrganizationSelect';
import PageHeader from '@/Components/PageHeader';
import StatusBadge from '@/Components/StatusBadge';
import StatusDropdown from '@/Components/StatusDropdown';
import TagEditor from '@/Components/TagEditor';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
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
import { usePermissions } from '@/lib/permissions';
import { type AdminProject, type SelectOption } from '@/types';
import ProjectForm from './Partials/ProjectForm';

interface Props {
    // Serialized CursorPaginator wrapped by Inertia::scroll(); the
    // <InfiniteScroll> component appends pages into `data` as the user scrolls.
    projects: { data: AdminProject[] };
    filters: {
        search: string;
        organization: string;
        status: string;
        inactive: boolean;
    };
    statusOptions: SelectOption[];
}

export default function Index({ projects, filters, statusOptions }: Props) {
    const { can } = usePermissions();
    const f = useFilters<Props['filters']>({
        route: 'projects.index',
        reset: ['projects'],
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminProject | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formProject, setFormProject] = useState<AdminProject | null>(null);
    // Bumped on every open so the form's key changes and it remounts with the
    // fresh record — re-editing the same row otherwise reuses a stale instance.
    const [formNonce, setFormNonce] = useState(0);

    const openCreate = () => {
        setFormProject(null);
        setFormNonce((n) => n + 1);
        setFormOpen(true);
    };

    const openEdit = (project: AdminProject) => {
        setFormProject(project);
        setFormNonce((n) => n + 1);
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
                    <OrganizationSelect
                        value={f.values.organization || undefined}
                        onChange={(v) => f.apply({ organization: v })}
                        allowClear
                        allLabel="All organizations"
                        className="w-56"
                    />
                    <FilterBar.Select
                        value={f.values.status || undefined}
                        onChange={(v) => f.apply({ status: v ?? '' })}
                        options={statusOptions}
                        allLabel="All statuses"
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
                    className="grid gap-3 xl:grid-cols-2"
                    loading={
                        <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin" />
                        </div>
                    }
                >
                    {projects.data.map((project) => (
                        <Card
                            key={project.token}
                            className="relative flex min-h-[4.75rem] items-stretch overflow-hidden transition-all hover:border-ring hover:shadow-md"
                        >
                            {/* Status as a leading, fully-clickable "addon" cell */}
                            <div className="flex items-stretch border-r bg-muted/30">
                                <Can
                                    ability="projects.update"
                                    fallback={
                                        <span className="flex items-center px-3">
                                            <StatusBadge
                                                status={project.status}
                                            />
                                        </span>
                                    }
                                >
                                    <StatusDropdown
                                        iconOnly
                                        variant="ghost"
                                        className="h-full w-auto rounded-none px-3"
                                        value={project.status}
                                        options={statusOptions}
                                        onSelect={async (status) => {
                                            await axios.patch(
                                                route(
                                                    'projects.status',
                                                    project.token,
                                                ),
                                                { status },
                                            );
                                            // A status filter is active: re-run it
                                            // so a project that no longer matches
                                            // drops out of the list.
                                            if (f.values.status) {
                                                f.submit();
                                            }
                                        }}
                                    />
                                </Can>
                            </div>

                            <div className="flex min-w-0 flex-1 items-center gap-3 p-3">
                                <div className="min-w-0 flex-1 space-y-1">
                                    <div className="flex items-center gap-2">
                                        <Link
                                            href={route(
                                                'projects.show',
                                                project.token,
                                            )}
                                            className="truncate font-medium after:absolute after:inset-0 focus-visible:outline-none"
                                        >
                                            {project.name}
                                        </Link>
                                        {project.private && (
                                            <Badge variant="secondary">
                                                Private
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {project.organization_name ??
                                            'No organization'}
                                        {project.description
                                            ? ` · ${project.description}`
                                            : ''}
                                    </p>
                                    <TagEditor
                                        tags={project.tags}
                                        organization={project.organization}
                                        type="projects"
                                        token={project.token}
                                        canEdit={can('projects.update')}
                                        singleRow
                                    />
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
                            </div>
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
                            key={`${formProject?.token ?? 'new'}-${formNonce}`}
                            project={formProject ?? undefined}
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
