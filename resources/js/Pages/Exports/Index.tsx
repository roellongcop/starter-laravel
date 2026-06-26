import { Head, Link, router } from '@inertiajs/react';
import { Download, Plus, RefreshCw } from 'lucide-react';

import Can from '@/Components/Can';
import CursorPager from '@/Components/CursorPager';
import FilterBar from '@/Components/FilterBar';
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
import { useFilters } from '@/hooks/use-filters';
import { useStatusPoll } from '@/hooks/use-status-poll';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminExport, type CursorResponse } from '@/types';

interface Props {
    exports: CursorResponse<AdminExport>;
    filters: { search: string };
    can: { create: boolean };
}

export default function Index({ exports, filters }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'exports.index',
        initial: filters,
    });

    useStatusPoll(
        exports.data.map((e) => e.status),
        'exports',
    );

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

            <FilterBar onSubmit={f.submit} className="mb-4">
                <FilterBar.Search
                    value={f.values.search}
                    onChange={(v) => f.set('search', v)}
                    placeholder="Search resource or format…"
                />
            </FilterBar>

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
                            <TableRow key={e.token}>
                                <TableCell>{e.resource}</TableCell>
                                <TableCell className="uppercase">
                                    {e.format}
                                </TableCell>
                                <TableCell>
                                    {e.status === 'Running' && e.total_rows ? (
                                        <div className="space-y-1">
                                            <div className="text-xs text-muted-foreground">
                                                {e.processed_rows.toLocaleString()}{' '}
                                                /{' '}
                                                {e.total_rows.toLocaleString()}
                                            </div>
                                            <div className="h-1.5 w-24 overflow-hidden rounded bg-muted">
                                                <div
                                                    className="h-full bg-primary"
                                                    style={{
                                                        width: `${Math.min(100, Math.round((e.processed_rows / e.total_rows) * 100))}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    ) : (
                                        (e.row_count ?? '—')
                                    )}
                                </TableCell>
                                <TableCell>
                                    <StatusBadge status={e.status} />
                                    {e.error_message && (
                                        <span
                                            className="ml-2 inline-block max-w-[16rem] truncate align-bottom text-xs text-destructive"
                                            title={e.error_message}
                                        >
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
                                            title="Download"
                                            aria-label="Download"
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
