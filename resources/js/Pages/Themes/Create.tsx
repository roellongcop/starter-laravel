import { Head } from '@inertiajs/react';

import BackButton from '@/Components/BackButton';
import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ThemeForm from './Partials/ThemeForm';

export default function Create() {
    return (
        <AuthenticatedLayout>
            <Head title="New Theme" />
            <PageHeader
                title="New Theme"
                actions={<BackButton fallback={route('themes.index')} />}
            />
            <Card>
                <CardContent className="pt-6">
                    <ThemeForm />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
