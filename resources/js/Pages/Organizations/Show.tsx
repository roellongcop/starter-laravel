import { Head } from '@inertiajs/react';
import { useState } from 'react';

import BackButton from '@/Components/BackButton.js';
import Can from '@/Components/Can';
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

    return (
        <AuthenticatedLayout>
            <Head title={organization.name} />

            <PageHeader
                title={organization.name}
                actions={
                    <>
                        <BackButton fallback={route('organizations.index')} />
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
