import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ThemeForm from './Partials/ThemeForm';

export default function Create() {
    return (
        <AuthenticatedLayout>
            <Head title="New Theme" />
            <PageHeader
                title="New Theme"
                breadcrumbs={[
                    { label: 'Themes', href: route('themes.index') },
                    { label: 'New Theme' },
                ]}
            />
            <ThemeForm />
        </AuthenticatedLayout>
    );
}
