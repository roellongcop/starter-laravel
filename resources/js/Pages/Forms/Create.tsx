import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type SelectOption } from '@/types';
import FormBuilder from './Partials/FormBuilder';

interface Props {
    fieldTypes: SelectOption[];
}

export default function Create({ fieldTypes }: Props) {
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
                    <FormBuilder fieldTypes={fieldTypes} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
