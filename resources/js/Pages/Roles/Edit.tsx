import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminRole, type MenuCatalogItem } from '@/types';
import RoleForm from './Partials/RoleForm';

interface Props {
    role: AdminRole;
    permissionGroups: Record<string, string[]>;
    menuCatalog: MenuCatalogItem[];
}

export default function Edit({ role, permissionGroups, menuCatalog }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${role.name}`} />
            <PageHeader
                title={`Edit ${role.name}`}
                breadcrumbs={[
                    { label: 'Roles', href: route('roles.index') },
                    { label: role.name, href: route('roles.show', role.token) },
                    { label: 'Edit' },
                ]}
            />
            <Card>
                <CardContent className="pt-6">
                    <RoleForm
                        role={role}
                        permissionGroups={permissionGroups}
                        menuCatalog={menuCatalog}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
