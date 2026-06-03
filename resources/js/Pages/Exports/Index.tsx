import { Head, Link, router } from '@inertiajs/react';
import { Download, Plus, RefreshCw } from 'lucide-react';

import Can from '@/Components/Can';
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
import { type AdminExport, type CursorResponse } from '@/types';

interface Props {
    exports: CursorResponse<AdminExport>;
    can: { create: boolean };
}

export default function Index({ exports }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="My Exports" />

            <PageHeader
                title="My Exports"
                description="Queued data exports — download when ready."
                actions={
                    <>
                        <Button
                            variant="outline"
                            onClick={() => router.reload({ only: ['exports'] })}
                        >
                            <RefreshCw className="h-4 w-4" /> Refresh
                        </Button>
                        <Can ability="exports.create">
                            <Button asChild>
                                <Link href={route('exports.create')}>
                                    <Plus className="h-4 w-4" /> New Export
                                </Link>
                            </Button>
                        </Can>
                    </>
                }
            />

            <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Resource</TableHead>
                            <TableHead>Format</TableHead>
                            <TableHead>Rows</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead className="text-right">
                                Download
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {exports.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="text-center text-muted-foreground"
                                >
                                    No exports yet.
                                </TableCell>
                            </TableRow>
                        )}
                        {exports.data.map((e) => (
                            <TableRow key={e.id}>
                                <TableCell>{e.resource}</TableCell>
                                <TableCell className="uppercase">
                                    {e.format}
                                </TableCell>
                                <TableCell>{e.row_count ?? '—'}</TableCell>
                                <TableCell>
                                    <StatusBadge status={e.status} />
                                    {e.error_message && (
                                        <span className="ml-2 text-xs text-destructive">
                                            {e.error_message}
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {e.created_at
                                        ? new Date(
                                              e.created_at,
                                          ).toLocaleString()
                                        : '—'}
                                </TableCell>
                                <TableCell className="text-right">
                                    {e.status === 'Done' && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            asChild
                                        >
                                            <a
                                                href={route(
                                                    'exports.download',
                                                    e.token,
                                                )}
                                            >
                                                <Download className="h-4 w-4" />
                                            </a>
                                        </Button>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <CursorPager
                    nextCursor={exports.next_cursor}
                    prevCursor={exports.prev_cursor}
                />
            </div>
        </AuthenticatedLayout>
    );
}
