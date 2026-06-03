import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type SelectOption } from '@/types';
import UserForm from './Partials/UserForm';

interface Props {
    roleOptions: string[];
    statusOptions: SelectOption[];
}

export default function Create({ roleOptions, statusOptions }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="New User" />
            <PageHeader title="New User" />
            <Card>
                <CardContent className="pt-6">
                    <UserForm
                        roleOptions={roleOptions}
                        statusOptions={statusOptions}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
