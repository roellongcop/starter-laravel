import { Head, router } from '@inertiajs/react';
import { Download, RefreshCw, RotateCcw, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import CursorPager from '@/Components/CursorPager';
import PageHeader from '@/Components/PageHeader';
import StatusBadge from '@/Components/StatusBadge';
import { Button } from '@/Components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminBackup, type CursorResponse } from '@/types';

interface Props {
    backups: CursorResponse<AdminBackup>;
    can: { create: boolean; restore: boolean; delete: boolean };
}

function humanSize(bytes: number | null): string {
    if (!bytes) return '—';
    const units = ['B', 'KB', 'MB', 'GB'];
    let n = bytes;
    let i = 0;
    while (n >= 1024 && i < units.length - 1) {
        n /= 1024;
        i++;
    }
    return `${n.toFixed(1)} ${units[i]}`;
}

export default function Index({ backups, can }: Props) {
    const [confirm, setConfirm] = useState<null | {
        action: 'restore' | 'delete';
        backup: AdminBackup;
    }>(null);

    const run = () => {
        if (!confirm) return;
        const { action, backup } = confirm;
        if (action === 'restore') {
            router.post(
                route('backups.restore', backup.id),
                {},
                { preserveScroll: true },
            );
        } else {
            router.delete(route('backups.destroy', backup.id), {
                preserveScroll: true,
            });
        }
        setConfirm(null);
    };

    return (
        <AuthenticatedLayout header="Backups">
            <Head title="Backups" />

            <PageHeader
                title="Backups"
                description="Database backups created and restored via queued jobs."
                actions={
                    <>
                        <Button
                            variant="outline"
                            onClick={() => router.reload({ only: ['backups'] })}
                        >
                            <RefreshCw className="h-4 w-4" /> Refresh
                        </Button>
                        <Can ability="backups.create">
                            <Button
                                onClick={() =>
                                    router.post(route('backups.store'))
                                }
                            >
                                Create backup
                            </Button>
                        </Can>
                    </>
                }
            />

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Filename</TableHead>
                            <TableHead>Size</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {backups.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="text-center text-muted-foreground"
                                >
                                    No backups yet.
                                </TableCell>
                            </TableRow>
                        )}
                        {backups.data.map((b) => (
                            <TableRow key={b.id}>
                                <TableCell className="font-mono text-sm">
                                    {b.filename ?? '—'}
                                </TableCell>
                                <TableCell>{humanSize(b.size)}</TableCell>
                                <TableCell>
                                    <StatusBadge status={b.status} />
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {b.created_at
                                        ? new Date(
                                              b.created_at,
                                          ).toLocaleString()
                                        : '—'}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-1">
                                        {b.status === 'Generated' && (
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                asChild
                                            >
                                                <a
                                                    href={route(
                                                        'backups.download',
                                                        b.id,
                                                    )}
                                                >
                                                    <Download className="h-4 w-4" />
                                                </a>
                                            </Button>
                                        )}
                                        {can.restore &&
                                            b.status === 'Generated' && (
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    onClick={() =>
                                                        setConfirm({
                                                            action: 'restore',
                                                            backup: b,
                                                        })
                                                    }
                                                >
                                                    <RotateCcw className="h-4 w-4" />
                                                </Button>
                                            )}
                                        <Can ability="backups.delete">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                onClick={() =>
                                                    setConfirm({
                                                        action: 'delete',
                                                        backup: b,
                                                    })
                                                }
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </Can>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <CursorPager
                    nextCursor={backups.next_cursor}
                    prevCursor={backups.prev_cursor}
                />
            </div>

            <ConfirmDialog
                open={confirm !== null}
                onOpenChange={(o) => !o && setConfirm(null)}
                title={
                    confirm?.action === 'restore'
                        ? 'Restore this backup?'
                        : 'Delete this backup?'
                }
                description={
                    confirm?.action === 'restore'
                        ? 'This overwrites the current database with the backup contents.'
                        : 'This permanently removes the backup archive.'
                }
                confirmLabel={
                    confirm?.action === 'restore' ? 'Restore' : 'Delete'
                }
                destructive
                onConfirm={run}
            />
        </AuthenticatedLayout>
    );
}
