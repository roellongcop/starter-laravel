import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AnswerValue, type Crumb, type FormDefinition } from '@/types';
import FieldInput from './Partials/FieldInput';

interface Props {
    form: FormDefinition;
}

export default function Respond({ form }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        answers: Record<string, AnswerValue>;
    }>({ answers: {} });

    const fieldErrors = errors as Record<string, string>;

    const setAnswer = (id: string, value: AnswerValue) =>
        setData('answers', { ...data.answers, [id]: value });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('forms.responses.store', form.token));
    };

    const breadcrumbs: Crumb[] = [
        { label: 'Forms', href: route('forms.index') },
        { label: form.title, href: route('forms.show', form.token) },
        { label: 'Respond' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Respond — ${form.title}`} />

            <PageHeader title={form.title} breadcrumbs={breadcrumbs} />

            <Card>
                <CardContent className="pt-6">
                    {form.description && (
                        <p className="mb-4 text-sm text-muted-foreground">
                            {form.description}
                        </p>
                    )}

                    <form onSubmit={submit} className="space-y-4">
                        {form.form_fields.map((field) => (
                            <FieldInput
                                key={field.id}
                                field={field}
                                value={data.answers[field.id] ?? null}
                                onChange={(v) => setAnswer(field.id, v)}
                                error={fieldErrors[`answers.${field.id}`]}
                            />
                        ))}

                        <Button type="submit" disabled={processing}>
                            Submit response
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
