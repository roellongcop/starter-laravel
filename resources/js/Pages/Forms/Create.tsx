import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type DataTagOption, type SelectOption } from '@/types';
import FormBuilder from './Partials/FormBuilder';

interface Props {
    organizations: SelectOption[];
    fieldTypes: SelectOption[];
    dataTags: DataTagOption[];
}

export default function Create({ organizations, fieldTypes, dataTags }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="New Form" />
            <PageHeader
                title="New Form"
                breadcrumbs={[
                    { label: 'Forms', href: route('forms.index') },
                    { label: 'New Form' },
                ]}
            />
            <Card>
                <CardContent className="pt-6">
                    <FormBuilder
                        organizations={organizations}
                        fieldTypes={fieldTypes}
                        dataTags={dataTags}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
