import { Head } from '@inertiajs/react';
import { useState } from 'react';

import Can from '@/Components/Can';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminProject, type Crumb, type SelectOption } from '@/types';
import ProjectForm from './Partials/ProjectForm';

interface Props {
    project: AdminProject;
    organizations: SelectOption[];
    // When reached via an organization (organizations/:token/projects/:token),
    // the breadcrumb trail is rooted at that organization instead of Projects.
    parentOrganization?: { token: string; name: string } | null;
}

export default function Show({
    project,
    organizations,
    parentOrganization,
}: Props) {
    const [editOpen, setEditOpen] = useState(false);

    const breadcrumbs: Crumb[] = parentOrganization
        ? [
              { label: 'Organizations', href: route('organizations.index') },
              {
                  label: parentOrganization.name,
                  href: route('organizations.show', parentOrganization.token),
              },
              { label: project.name },
          ]
        : [
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
                    </>
                }
            />

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
                                    project.private ? 'secondary' : 'outline'
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
                </CardContent>
            </Card>

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
                            onSuccess={() => setEditOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>
        </AuthenticatedLayout>
    );
}
