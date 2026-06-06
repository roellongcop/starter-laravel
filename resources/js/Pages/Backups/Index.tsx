import { Head, router } from '@inertiajs/react';
import {
    AlertCircle,
    Download,
    RefreshCw,
    RotateCcw,
    Trash2,
} from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import CursorPager from '@/Components/CursorPager';
import PageHeader from '@/Components/PageHeader';
import StatusBadge from '@/Components/StatusBadge';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
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
import { type AdminBackup, type CursorResponse } from '@/types';

interface Props {
    backups: CursorResponse<AdminBackup>;
    filters: { search: string };
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

export default function Index({ backups, filters, can }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [confirm, setConfirm] = useState<null | {
        action: 'restore' | 'delete';
        backup: AdminBackup;
    }>(null);
    const [errorDetail, setErrorDetail] = useState<AdminBackup | null>(null);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(
            route('backups.index'),
            { search },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const run = () => {
        if (!confirm) return;
        const { action, backup } = confirm;
        if (action === 'restore') {
            router.post(
                route('backups.restore', backup.token),
                {},
                { preserveScroll: true },
            );
        } else {
            router.delete(route('backups.destroy', backup.token), {
                preserveScroll: true,
            });
        }
        setConfirm(null);
    };

    return (
        <AuthenticatedLayout>
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

            <form onSubmit={submitSearch} className="mb-4 flex gap-2">
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search filename…"
                    className="w-64"
                />
                <Button type="submit" variant="secondary">
                    Search
                </Button>
            </form>

            <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
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
                            <TableRow key={b.token}>
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
                                        {(b.status === 'Failed' ||
                                            b.status === 'RestoreFailed') && (
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="View failure details"
                                                aria-label="View failure details"
                                                onClick={() =>
                                                    setErrorDetail(b)
                                                }
                                            >
                                                <AlertCircle className="h-4 w-4 text-destructive" />
                                            </Button>
                                        )}
                                        {(b.status === 'Generated' ||
                                            b.status === 'Restored') && (
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Download backup"
                                                aria-label="Download backup"
                                                asChild
                                            >
                                                <a
                                                    href={route(
                                                        'backups.download',
                                                        b.token,
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
                                                    title="Restore backup"
                                                    aria-label="Restore backup"
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
                                                title="Delete backup"
                                                aria-label="Delete backup"
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

            <Dialog
                open={errorDetail !== null}
                onOpenChange={(o) => !o && setErrorDetail(null)}
            >
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Backup failed</DialogTitle>
                        <DialogDescription>
                            {errorDetail?.filename ?? 'Backup'} —{' '}
                            {errorDetail?.created_at
                                ? new Date(
                                      errorDetail.created_at,
                                  ).toLocaleString()
                                : ''}
                        </DialogDescription>
                    </DialogHeader>
                    <pre className="max-h-[60vh] overflow-auto whitespace-pre-wrap break-words rounded bg-muted p-3 text-xs">
                        {errorDetail?.error_message ??
                            'No details were captured for this failure.'}
                    </pre>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
