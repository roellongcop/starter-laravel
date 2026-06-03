import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RoleForm from './Partials/RoleForm';

interface Props {
    permissionGroups: Record<string, string[]>;
}

export default function Create({ permissionGroups }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="New Role" />
            <PageHeader title="New Role" />
            <Card>
                <CardContent className="pt-6">
                    <RoleForm permissionGroups={permissionGroups} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
