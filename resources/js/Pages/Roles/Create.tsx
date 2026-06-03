import { Head } from '@inertiajs/react';

import BackButton from '@/Components/BackButton';
import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type MenuCatalogItem } from '@/types';
import RoleForm from './Partials/RoleForm';

interface Props {
    permissionGroups: Record<string, string[]>;
    menuCatalog: MenuCatalogItem[];
}

export default function Create({ permissionGroups, menuCatalog }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="New Role" />
            <PageHeader
                title="New Role"
                actions={<BackButton fallback={route('roles.index')} />}
            />
            <Card>
                <CardContent className="pt-6">
                    <RoleForm
                        permissionGroups={permissionGroups}
                        menuCatalog={menuCatalog}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
