import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    type AdminDocument,
    type AdminUser,
    type CursorResponse,
    type SelectOption,
} from '@/types';
import UserForm from './Partials/UserForm';

interface Props {
    user: AdminUser;
    roleOptions: string[];
    statusOptions: SelectOption[];
    documents: CursorResponse<AdminDocument>;
}

export default function Edit({
    user,
    roleOptions,
    statusOptions,
    documents,
}: Props) {
    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${user.name}`} />
            <PageHeader
                title={`Edit ${user.name}`}
                breadcrumbs={[
                    { label: 'Users', href: route('users.index') },
                    { label: user.name, href: route('users.show', user.token) },
                    { label: 'Edit' },
                ]}
            />
            <Card>
                <CardContent className="pt-6">
                    <UserForm
                        user={user}
                        roleOptions={roleOptions}
                        statusOptions={statusOptions}
                        documents={documents}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
