import { Head, InfiniteScroll, Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
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
import { type AdminOrganization, type SelectOption } from '@/types';
import OrganizationForm from './Partials/OrganizationForm';

interface OrganizationProject {
    token: string;
    name: string;
    private: boolean;
    description: string | null;
}

interface Props {
    organization: AdminOrganization;
    // Keyset-paginated via Inertia::scroll(); <InfiniteScroll> appends pages.
    projects: { data: OrganizationProject[] };
    projectsTotal: number;
    projectFilters: { search: string };
    users: SelectOption[];
}

export default function Show({
    organization,
    projects,
    projectsTotal,
    projectFilters,
    users,
}: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const f = useFilters<{ search: string }>({
        route: 'organizations.show',
        params: organization.token,
        reset: ['projects'],
        initial: { search: projectFilters.search },
    });

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
                                    className="relative flex flex-col transition-shadow focus-within:ring-2 focus-within:ring-ring hover:shadow-md"
                                >
                                    <CardHeader>
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
        </AuthenticatedLayout>
    );
}
