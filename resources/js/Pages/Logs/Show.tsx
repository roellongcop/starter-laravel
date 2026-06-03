import { Head, Link } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminAudit } from '@/types';

function ValueBlock({
    title,
    values,
}: {
    title: string;
    values?: Record<string, unknown>;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <pre className="overflow-auto rounded bg-muted p-3 text-xs">
                    {JSON.stringify(values ?? {}, null, 2)}
                </pre>
            </CardContent>
        </Card>
    );
}

export default function Show({ log }: { log: AdminAudit }) {
    return (
        <AuthenticatedLayout>
            <Head title="Audit Detail" />
            <PageHeader
                title={`${log.event} ${log.auditable_type} #${log.auditable_id}`}
                description={`${log.user} · ${log.browser} / ${log.os} / ${log.device}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href={route('logs.index')}>Back</Link>
                    </Button>
                }
            />

            <div className="mb-6 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <span className="text-muted-foreground">URL</span>
                    <div className="break-all">{log.url || '—'}</div>
                </div>
                <div>
                    <span className="text-muted-foreground">IP</span>
                    <div>{log.ip_address || '—'}</div>
                </div>
                <div>
                    <span className="text-muted-foreground">Tags</span>
                    <div>{log.tags || '—'}</div>
                </div>
                <div>
                    <span className="text-muted-foreground">When</span>
                    <div>
                        {log.created_at
                            ? new Date(log.created_at).toLocaleString()
                            : '—'}
                    </div>
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <ValueBlock title="Old values" values={log.old_values} />
                <ValueBlock title="New values" values={log.new_values} />
            </div>
        </AuthenticatedLayout>
    );
}
