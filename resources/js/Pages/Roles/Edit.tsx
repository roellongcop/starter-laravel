import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminRole } from '@/types';
import RoleForm from './Partials/RoleForm';

interface Props {
    role: AdminRole;
    permissionGroups: Record<string, string[]>;
}

export default function Edit({ role, permissionGroups }: Props) {
    return (
        <AuthenticatedLayout header={`Edit ${role.name}`}>
            <Head title={`Edit ${role.name}`} />
            <PageHeader title={`Edit ${role.name}`} />
            <Card>
                <CardContent className="pt-6">
                    <RoleForm role={role} permissionGroups={permissionGroups} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
