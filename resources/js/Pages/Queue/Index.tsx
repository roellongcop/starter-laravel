import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import ConfirmDialog from '@/Components/ConfirmDialog';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type QueueStats } from '@/types';

interface PendingJob {
    id: number;
    queue: string;
    name: string;
    attempts: number;
}
interface FailedJob {
    id: number;
    uuid: string;
    queue: string;
    name: string;
    failed_at: string;
}

interface Props {
    stats: QueueStats;
    pending: PendingJob[];
    failed: FailedJob[];
    can: { manage: boolean };
}

export default function Index({ stats, pending, failed, can }: Props) {
    const [confirm, setConfirm] = useState<null | 'failed' | 'pending'>(null);

    const post = (name: string, data: Record<string, string> = {}) =>
        router.post(route(name), data, { preserveScroll: true });

    return (
        <AuthenticatedLayout>
            <Head title="Queue" />
            <PageHeader
                title="Queue Monitor"
                description="Pending and failed database queue jobs."
                actions={
                    can.manage && (
                        <>
                            <Button
                                variant="outline"
                                onClick={() => post('queue.retry')}
                            >
                                Retry failed
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setConfirm('pending')}
                            >
                                Clear pending
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={() => setConfirm('failed')}
                            >
                                Clear failed
                            </Button>
                        </>
                    )
                }
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Pending</CardTitle>
                    </CardHeader>
                    <CardContent className="text-3xl font-semibold">
                        {stats.pending}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Failed</CardTitle>
                    </CardHeader>
                    <CardContent className="text-3xl font-semibold text-destructive">
                        {stats.failed}
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <div className="min-w-0 rounded-lg border bg-card text-card-foreground shadow-sm">
                    <div className="border-b px-4 py-2 font-medium">
                        Pending jobs
                    </div>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Job</TableHead>
                                <TableHead>Queue</TableHead>
                                <TableHead>Attempts</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pending.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={3}
                                        className="text-center text-muted-foreground"
                                    >
                                        None.
                                    </TableCell>
                                </TableRow>
                            )}
                            {pending.map((j) => (
                                <TableRow key={j.id}>
                                    <TableCell
                                        className="max-w-xs truncate text-sm"
                                        title={j.name}
                                    >
                                        {j.name}
                                    </TableCell>
                                    <TableCell>{j.queue}</TableCell>
                                    <TableCell>{j.attempts}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <div className="min-w-0 rounded-lg border bg-card text-card-foreground shadow-sm">
                    <div className="border-b px-4 py-2 font-medium">
                        Failed jobs
                    </div>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Job</TableHead>
                                <TableHead>Failed at</TableHead>
                                <TableHead className="text-right">
                                    Retry
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {failed.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={3}
                                        className="text-center text-muted-foreground"
                                    >
                                        None.
                                    </TableCell>
                                </TableRow>
                            )}
                            {failed.map((j) => (
                                <TableRow key={j.id}>
                                    <TableCell
                                        className="max-w-xs truncate text-sm"
                                        title={j.name}
                                    >
                                        {j.name}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {j.failed_at
                                            ? new Date(
                                                  j.failed_at,
                                              ).toLocaleString()
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {can.manage && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    post('queue.retry', {
                                                        uuid: j.uuid,
                                                    })
                                                }
                                            >
                                                Retry
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <ConfirmDialog
                open={confirm !== null}
                onOpenChange={(o) => !o && setConfirm(null)}
                title={
                    confirm === 'failed'
                        ? 'Clear all failed jobs?'
                        : 'Clear all pending jobs?'
                }
                confirmLabel="Clear"
                destructive
                onConfirm={() => {
                    post(
                        confirm === 'failed'
                            ? 'queue.clear-failed'
                            : 'queue.clear-pending',
                    );
                    setConfirm(null);
                }}
            />
        </AuthenticatedLayout>
    );
}
