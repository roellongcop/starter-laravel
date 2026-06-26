import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
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
            <ThemeForm theme={theme} />
        </AuthenticatedLayout>
    );
}
