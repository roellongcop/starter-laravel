import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminForm, type DataTagOption, type SelectOption } from '@/types';
import FormBuilder from './Partials/FormBuilder';

interface Props {
    form: AdminForm;
    organizations: SelectOption[];
    fieldTypes: SelectOption[];
    dataTags: DataTagOption[];
}

export default function Edit({
    form,
    organizations,
    fieldTypes,
    dataTags,
}: Props) {
    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${form.title}`} />
            <PageHeader
                title={`Edit ${form.title}`}
                breadcrumbs={[
                    { label: 'Forms', href: route('forms.index') },
                    {
                        label: form.title,
                        href: route('forms.show', form.token),
                    },
                    { label: 'Edit' },
                ]}
            />
            <Card>
                <CardContent className="pt-6">
                    <FormBuilder
                        form={form}
                        organizations={organizations}
                        fieldTypes={fieldTypes}
                        dataTags={dataTags}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
