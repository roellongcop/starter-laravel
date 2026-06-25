import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    type AdminFormResponse,
    type Crumb,
    type FormDefinition,
} from '@/types';

interface Props {
    form: FormDefinition;
    responses: { data: AdminFormResponse[] };
    responsesTotal: number;
}

export default function Responses({ form, responses, responsesTotal }: Props) {
    const [deleting, setDeleting] = useState<AdminFormResponse | null>(null);

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('responses.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    const breadcrumbs: Crumb[] = [
        { label: 'Forms', href: route('forms.index') },
        { label: form.title, href: route('forms.show', form.token) },
        { label: 'Responses' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Responses — ${form.title}`} />

            <PageHeader
                title={`${form.title} — Responses`}
                breadcrumbs={breadcrumbs}
                description={`${responsesTotal} response${responsesTotal === 1 ? '' : 's'}`}
            />

            {responses.data.length === 0 ? (
                <div className="rounded-lg border bg-card py-16 text-center text-sm text-muted-foreground">
                    No responses yet.
                </div>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <InfiniteScroll data="responses">
                            <ul className="divide-y">
                                {responses.data.map((response) => (
                                    <li
                                        key={response.token}
                                        className="relative flex items-center justify-between gap-2 px-4 py-3 hover:bg-muted/40"
                                    >
                                        <div>
                                            <Link
                                                href={route(
                                                    'responses.show',
                                                    response.token,
                                                )}
                                                className="font-medium after:absolute after:inset-0 focus-visible:outline-none"
                                            >
                                                {response.respondent ??
                                                    'Anonymous'}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">
                                                {response.created_at
                                                    ? new Date(
                                                          response.created_at,
                                                      ).toLocaleString()
                                                    : '—'}
                                            </p>
                                        </div>
                                        <Can ability="form-responses.delete">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                className="relative z-10"
                                                aria-label="Delete response"
                                                onClick={() =>
                                                    setDeleting(response)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </Can>
                                    </li>
                                ))}
                            </ul>
                        </InfiniteScroll>
                    </CardContent>
                </Card>
            )}

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
                title="Delete this response?"
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
