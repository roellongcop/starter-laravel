import { Head, router } from '@inertiajs/react';
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
import {
    type AdminOrganizationRole,
    type Crumb,
    type SelectOption,
} from '@/types';
import OrganizationRoleForm from './Partials/OrganizationRoleForm';

interface Props {
    role: AdminOrganizationRole;
    organizations: SelectOption[];
}

export default function Show({ role, organizations }: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    const destroy = () =>
        router.delete(route('organization-roles.destroy', role.token), {
            onFinish: () => setConfirmingDelete(false),
        });

    const breadcrumbs: Crumb[] = [
        {
            label: 'Organization Roles',
            href: route('organization-roles.index'),
        },
        { label: role.name },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={role.name} />

            <PageHeader
                title={role.name}
                breadcrumbs={breadcrumbs}
                actions={
                    <>
                        <Can ability="organization-roles.update">
                            <Button onClick={() => setEditOpen(true)}>
                                Edit
                            </Button>
                        </Can>
                        <Can ability="organization-roles.delete">
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
                            Organization
                        </span>
                        <p className="mt-1 text-sm">
                            {role.organization_name || '—'}
                        </p>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Description
                        </span>
                        <p className="mt-1 text-sm">
                            {role.description || '—'}
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
                        <SheetTitle>{`Edit ${role.name}`}</SheetTitle>
                        <SheetDescription>
                            A role defined within an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <OrganizationRoleForm
                            role={role}
                            organizations={organizations}
                            onSuccess={() => setEditOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <ConfirmDialog
                open={confirmingDelete}
                onOpenChange={setConfirmingDelete}
                title={`Delete ${role.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
