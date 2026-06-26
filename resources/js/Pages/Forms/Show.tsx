import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import PageHeader from '@/Components/PageHeader';
import TagBadges from '@/Components/TagBadges';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminForm, type Crumb } from '@/types';
import FieldInput from './Partials/FieldInput';

interface Props {
    form: AdminForm;
}

export default function Show({ form }: Props) {
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    const destroy = () =>
        router.delete(route('forms.destroy', form.token), {
            onFinish: () => setConfirmingDelete(false),
        });

    const breadcrumbs: Crumb[] = [
        { label: 'Forms', href: route('forms.index') },
        { label: form.title },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={form.title} />

            <PageHeader
                title={form.title}
                breadcrumbs={breadcrumbs}
                actions={
                    <>
                        <Can ability="forms.show">
                            <Button asChild variant="secondary">
                                <Link href={route('forms.respond', form.token)}>
                                    Respond
                                </Link>
                            </Button>
                        </Can>
                        <Can ability="form-responses.index">
                            <Button asChild variant="outline">
                                <Link
                                    href={route(
                                        'forms.responses.index',
                                        form.token,
                                    )}
                                >
                                    Responses
                                    {typeof form.responses_count === 'number' &&
                                        ` (${form.responses_count})`}
                                </Link>
                            </Button>
                        </Can>
                        <Can ability="forms.update">
                            <Button asChild>
                                <Link href={route('forms.edit', form.token)}>
                                    Edit
                                </Link>
                            </Button>
                        </Can>
                        <Can ability="forms.delete">
                            <Button
                                variant="destructive"
                                onClick={() => setConfirmingDelete(true)}
                            >
                                Delete
                            </Button>
                        </Can>
                    </>
                }
            />

            <Card>
                <CardContent className="space-y-4 pt-6">
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Organization
                        </span>
                        <p className="mt-1 text-sm">
                            {form.organization_name || '—'}
                        </p>
                    </div>
                    {form.tags.length > 0 && (
                        <div>
                            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                Tags
                            </span>
                            <TagBadges tags={form.tags} className="mt-1" />
                        </div>
                    )}
                    {form.description && (
                        <p className="text-sm text-muted-foreground">
                            {form.description}
                        </p>
                    )}

                    {form.form_fields.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            This form has no fields yet.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {form.form_fields.map((field) => (
                                <FieldInput
                                    key={field.id}
                                    field={field}
                                    value={null}
                                    onChange={() => undefined}
                                    disabled
                                />
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            <ConfirmDialog
                open={confirmingDelete}
                onOpenChange={setConfirmingDelete}
                title={`Delete ${form.title}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
