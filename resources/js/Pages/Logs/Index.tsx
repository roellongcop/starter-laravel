import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

import CursorPager from '@/Components/CursorPager';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminAudit, type CursorResponse } from '@/types';

interface Props {
    logs: CursorResponse<AdminAudit>;
    filters: { event: string; type: string };
}

const eventColor: Record<string, 'default' | 'secondary' | 'destructive'> = {
    created: 'default',
    updated: 'secondary',
    deleted: 'destructive',
};

export default function Index({ logs, filters }: Props) {
    const [type, setType] = useState(filters.type);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(
            route('logs.index'),
            { type, event: filters.event },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Audit Logs" />
            <PageHeader
                title="Audit Logs"
                description="Read-only model change history."
            />

            <form onSubmit={submit} className="mb-4 flex gap-2">
                <Input
                    value={type}
                    onChange={(e) => setType(e.target.value)}
                    placeholder="Filter by model type…"
                    className="w-72"
                />
                <Button type="submit" variant="secondary">
                    Filter
                </Button>
            </form>

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Event</TableHead>
                            <TableHead>Model</TableHead>
                            <TableHead>User</TableHead>
                            <TableHead>Browser / OS</TableHead>
                            <TableHead>When</TableHead>
                            <TableHead className="text-right">View</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {logs.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="text-center text-muted-foreground"
                                >
                                    No audit records.
                                </TableCell>
                            </TableRow>
                        )}
                        {logs.data.map((a) => (
                            <TableRow key={a.id}>
                                <TableCell>
                                    <Badge
                                        variant={
                                            eventColor[a.event] ?? 'outline'
                                        }
                                    >
                                        {a.event}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-sm">
                                    {a.auditable_type} #{a.auditable_id}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {a.user}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {a.browser} / {a.os}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {a.created_at
                                        ? new Date(
                                              a.created_at,
                                          ).toLocaleString()
                                        : '—'}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button size="sm" variant="ghost" asChild>
                                        <Link href={route('logs.show', a.id)}>
                                            Details
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <CursorPager
                    nextCursor={logs.next_cursor}
                    prevCursor={logs.prev_cursor}
                />
            </div>
        </AuthenticatedLayout>
    );
}
