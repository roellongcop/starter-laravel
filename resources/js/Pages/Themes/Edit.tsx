import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminTheme } from '@/types';
import ThemeForm from './Partials/ThemeForm';

export default function Edit({ theme }: { theme: AdminTheme }) {
    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${theme.name}`} />
            <PageHeader
                title={`Edit ${theme.name}`}
                breadcrumbs={[
                    { label: 'Themes', href: route('themes.index') },
                    {
                        label: theme.name,
                        href: route('themes.show', theme.token),
                    },
                    { label: 'Edit' },
                ]}
            />
            <Card>
                <CardContent className="pt-6">
                    <ThemeForm theme={theme} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
