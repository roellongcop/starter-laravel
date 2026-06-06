import { Head, Link, router } from '@inertiajs/react';
import { Download, FileWarning, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import CursorPager from '@/Components/CursorPager';
import PageHeader from '@/Components/PageHeader';
import StatusBadge from '@/Components/StatusBadge';
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
import { useStatusPoll } from '@/hooks/use-status-poll';
import { type AdminImport, type CursorResponse } from '@/types';

interface Props {
    imports: CursorResponse<AdminImport>;
    filters: { search: string };
    can: { create: boolean };
}

export default function Index({ imports, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [confirm, setConfirm] = useState<AdminImport | null>(null);

    useStatusPoll(
        imports.data.map((i) => i.status),
        'imports',
    );

    const remove = () => {
        if (!confirm) return;
        router.delete(route('imports.destroy', confirm.token), {
            preserveScroll: true,
        });
        setConfirm(null);
    };

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(
            route('imports.index'),
            { search },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="My Imports" />

            <PageHeader
                title="My Imports"
                description="Upload a spreadsheet, preview, then process."
                actions={
                    <>
                        <Button
                            variant="outline"
                            onClick={() => router.reload({ only: ['imports'] })}
                        >
                            <RefreshCw className="h-4 w-4" /> Refresh
                        </Button>
                        <Can ability="imports.create">
                            <Button asChild>
                                <Link href={route('imports.create')}>
                                    <Plus className="h-4 w-4" /> New Import
                                </Link>
                            </Button>
                        </Can>
                    </>
                }
            />

            <form onSubmit={submitSearch} className="mb-4 flex gap-2">
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search resource or filename…"
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
                            <TableHead>Resource</TableHead>
                            <TableHead>Total</TableHead>
                            <TableHead>Success</TableHead>
                            <TableHead>Failed</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {imports.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="text-center text-muted-foreground"
                                >
                                    No imports yet.
                                </TableCell>
                            </TableRow>
                        )}
                        {imports.data.map((i) => (
                            <TableRow key={i.token}>
                                <TableCell>{i.resource}</TableCell>
                                <TableCell>{i.total}</TableCell>
                                <TableCell className="text-green-600">
                                    {i.success}
                                </TableCell>
                                <TableCell className="text-destructive">
                                    {i.failed}
                                </TableCell>
                                <TableCell>
                                    <StatusBadge status={i.status} />
                                    {i.status === 'Running' && i.total > 0 && (
                                        <div className="mt-1 h-1.5 w-24 overflow-hidden rounded bg-muted">
                                            <div
                                                className="h-full bg-primary"
                                                style={{
                                                    width: `${Math.min(100, Math.round(((i.success + i.failed) / i.total) * 100))}%`,
                                                }}
                                            />
                                        </div>
                                    )}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-1">
                                        {i.filename && (
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Download file"
                                                aria-label="Download file"
                                                asChild
                                            >
                                                <a
                                                    href={route(
                                                        'imports.download',
                                                        i.token,
                                                    )}
                                                >
                                                    <Download className="h-4 w-4" />
                                                </a>
                                            </Button>
                                        )}
                                        {i.has_error_report && (
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Download error report"
                                                aria-label="Download error report"
                                                asChild
                                            >
                                                <a
                                                    href={route(
                                                        'imports.errors',
                                                        i.token,
                                                    )}
                                                >
                                                    <FileWarning className="h-4 w-4" />
                                                </a>
                                            </Button>
                                        )}
                                        {i.status !== 'Running' && (
                                            <Can ability="imports.delete">
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    title="Delete import"
                                                    aria-label="Delete import"
                                                    onClick={() =>
                                                        setConfirm(i)
                                                    }
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            </Can>
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <CursorPager
                    nextCursor={imports.next_cursor}
                    prevCursor={imports.prev_cursor}
                />
            </div>

            <ConfirmDialog
                open={confirm !== null}
                onOpenChange={(o) => !o && setConfirm(null)}
                title="Delete this import?"
                description="This permanently removes the import record, its uploaded file, and any error report."
                confirmLabel="Delete"
                destructive
                onConfirm={remove}
            />
        </AuthenticatedLayout>
    );
}
