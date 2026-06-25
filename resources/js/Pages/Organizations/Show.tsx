import { Head, Link, router } from '@inertiajs/react';
import {
    BadgeCheck,
    Boxes,
    ChevronRight,
    ClipboardList,
    FileText,
    FolderKanban,
    UsersRound,
} from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import PageHeader from '@/Components/PageHeader';
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
import { type AdminOrganization, type SelectOption } from '@/types';
import OrganizationForm from './Partials/OrganizationForm';

interface Props {
    organization: AdminOrganization;
    users: SelectOption[];
}

export default function Show({ organization, users }: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    const destroy = () =>
        router.delete(route('organizations.destroy', organization.token), {
            onFinish: () => setConfirmingDelete(false),
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

            <div className="mt-6 grid gap-4 sm:grid-cols-2">
                <Can ability="projects.index">
                    <Card className="relative transition-shadow hover:shadow-md">
                        <CardContent className="flex items-center gap-4 pt-6">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                                <FolderKanban className="h-5 w-5" />
                            </div>
                            <div className="flex-1">
                                {/* Stretched link → projects index filtered to
                                    this organization (?organization=token). */}
                                <Link
                                    href={route('projects.index', {
                                        organization: organization.token,
                                    })}
                                    className="font-medium after:absolute after:inset-0 focus-visible:outline-none"
                                >
                                    Projects
                                </Link>
                                <p className="text-sm text-muted-foreground">
                                    View projects for this organization
                                </p>
                            </div>
                            <ChevronRight className="h-5 w-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                </Can>

                <Can ability="assets.index">
                    <Card className="relative transition-shadow hover:shadow-md">
                        <CardContent className="flex items-center gap-4 pt-6">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                                <Boxes className="h-5 w-5" />
                            </div>
                            <div className="flex-1">
                                <Link
                                    href={route('assets.index', {
                                        organization: organization.token,
                                    })}
                                    className="font-medium after:absolute after:inset-0 focus-visible:outline-none"
                                >
                                    Assets
                                </Link>
                                <p className="text-sm text-muted-foreground">
                                    View assets for this organization
                                </p>
                            </div>
                            <ChevronRight className="h-5 w-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                </Can>

                <Can ability="forms.index">
                    <Card className="relative transition-shadow hover:shadow-md">
                        <CardContent className="flex items-center gap-4 pt-6">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                                <ClipboardList className="h-5 w-5" />
                            </div>
                            <div className="flex-1">
                                <Link
                                    href={route('forms.index', {
                                        organization: organization.token,
                                    })}
                                    className="font-medium after:absolute after:inset-0 focus-visible:outline-none"
                                >
                                    Forms
                                </Link>
                                <p className="text-sm text-muted-foreground">
                                    View forms for this organization
                                </p>
                            </div>
                            <ChevronRight className="h-5 w-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                </Can>

                <Can ability="organization-roles.index">
                    <Card className="relative transition-shadow hover:shadow-md">
                        <CardContent className="flex items-center gap-4 pt-6">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                                <BadgeCheck className="h-5 w-5" />
                            </div>
                            <div className="flex-1">
                                <Link
                                    href={route('organization-roles.index', {
                                        organization: organization.token,
                                    })}
                                    className="font-medium after:absolute after:inset-0 focus-visible:outline-none"
                                >
                                    Organization Roles
                                </Link>
                                <p className="text-sm text-muted-foreground">
                                    View roles for this organization
                                </p>
                            </div>
                            <ChevronRight className="h-5 w-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                </Can>

                <Can ability="teams.index">
                    <Card className="relative transition-shadow hover:shadow-md">
                        <CardContent className="flex items-center gap-4 pt-6">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                                <UsersRound className="h-5 w-5" />
                            </div>
                            <div className="flex-1">
                                <Link
                                    href={route('teams.index', {
                                        organization: organization.token,
                                    })}
                                    className="font-medium after:absolute after:inset-0 focus-visible:outline-none"
                                >
                                    Teams and People
                                </Link>
                                <p className="text-sm text-muted-foreground">
                                    View teams and people for this organization
                                </p>
                            </div>
                            <ChevronRight className="h-5 w-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                </Can>

                <Can ability="reference-files.index">
                    <Card className="relative transition-shadow hover:shadow-md">
                        <CardContent className="flex items-center gap-4 pt-6">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                                <FileText className="h-5 w-5" />
                            </div>
                            <div className="flex-1">
                                <Link
                                    href={route('reference-files.index', {
                                        organization: organization.token,
                                    })}
                                    className="font-medium after:absolute after:inset-0 focus-visible:outline-none"
                                >
                                    Reference Files
                                </Link>
                                <p className="text-sm text-muted-foreground">
                                    View reference files for this organization
                                </p>
                            </div>
                            <ChevronRight className="h-5 w-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                </Can>
            </div>

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

            <ConfirmDialog
                open={confirmingDelete}
                onOpenChange={setConfirmingDelete}
                title={`Delete ${organization.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
