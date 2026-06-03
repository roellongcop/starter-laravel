import { Head, Link } from '@inertiajs/react';
import { Download } from 'lucide-react';

import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminFile } from '@/types';

function Field({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div>
            <dt className="text-xs uppercase tracking-wide text-muted-foreground">
                {label}
            </dt>
            <dd className="mt-1 text-sm">{value || '—'}</dd>
        </div>
    );
}

export default function Show({ file }: { file: AdminFile }) {
    return (
        <AuthenticatedLayout>
            <Head title={file.original_name} />

            <PageHeader
                title={file.original_name}
                description={file.tag ?? undefined}
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <Link href={route('files.index')}>Back</Link>
                        </Button>
                        <Button asChild>
                            <a href={route('files.download', file.id)}>
                                <Download className="h-4 w-4" /> Download
                            </a>
                        </Button>
                    </>
                }
            />

            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Metadata</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-2 gap-4">
                            <Field label="Extension" value={file.extension} />
                            <Field label="MIME" value={file.mime} />
                            <Field label="Disk" value={file.disk} />
                            <Field label="Owner" value={file.owner} />
                            <Field label="Path" value={file.path} />
                        </dl>
                    </CardContent>
                </Card>

                {file.is_image && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Preview</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <img
                                src={route('files.preview', file.id)}
                                alt={file.original_name}
                                className="max-h-80 rounded border"
                            />
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
