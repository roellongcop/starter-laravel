import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    type AdminFormResponse,
    type AnswerValue,
    type Crumb,
    type FormDefinition,
} from '@/types';

interface Props {
    form: FormDefinition;
    response: AdminFormResponse;
}

function formatAnswer(value: AnswerValue): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }
    if (Array.isArray(value)) {
        return value.length ? value.join(', ') : '—';
    }
    return String(value);
}

export default function ResponseShow({ form, response }: Props) {
    const breadcrumbs: Crumb[] = [
        { label: 'Forms', href: route('forms.index') },
        { label: form.title, href: route('forms.show', form.token) },
        {
            label: 'Responses',
            href: route('forms.responses.index', form.token),
        },
        { label: response.respondent ?? 'Response' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Response — ${form.title}`} />

            <PageHeader
                title={response.respondent ?? 'Response'}
                breadcrumbs={breadcrumbs}
                description={
                    response.created_at
                        ? new Date(response.created_at).toLocaleString()
                        : undefined
                }
            />

            <Card>
                <CardContent className="space-y-4 pt-6">
                    {form.form_fields.map((field) => (
                        <div key={field.id}>
                            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                {field.label || 'Untitled field'}
                            </span>
                            <p className="mt-1 whitespace-pre-wrap text-sm">
                                {formatAnswer(response.answers[field.id])}
                            </p>
                        </div>
                    ))}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
