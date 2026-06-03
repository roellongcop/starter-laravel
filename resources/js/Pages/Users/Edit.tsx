import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminUser, type SelectOption } from '@/types';
import UserForm from './Partials/UserForm';

interface Props {
    user: AdminUser;
    roleOptions: string[];
    statusOptions: SelectOption[];
}

export default function Edit({ user, roleOptions, statusOptions }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${user.name}`} />
            <PageHeader title={`Edit ${user.name}`} />
            <Card>
                <CardContent className="pt-6">
                    <UserForm
                        user={user}
                        roleOptions={roleOptions}
                        statusOptions={statusOptions}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
