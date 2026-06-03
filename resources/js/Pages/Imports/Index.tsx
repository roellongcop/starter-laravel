import { Head, Link, router } from '@inertiajs/react';
import { FileWarning, Plus, RefreshCw } from 'lucide-react';

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
import { type AdminImport, type CursorResponse } from '@/types';

interface Props {
    imports: CursorResponse<AdminImport>;
    can: { create: boolean };
}

export default function Index({ imports }: Props) {
    return (
        <AuthenticatedLayout header="My Imports">
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

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Resource</TableHead>
                            <TableHead>Total</TableHead>
                            <TableHead>Success</TableHead>
                            <TableHead>Failed</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Errors</TableHead>
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
                            <TableRow key={i.id}>
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
                                </TableCell>
                                <TableCell className="text-right">
                                    {i.has_error_report && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            asChild
                                        >
                                            <a
                                                href={route(
                                                    'imports.errors',
                                                    i.id,
                                                )}
                                            >
                                                <FileWarning className="h-4 w-4" />
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
                    nextCursor={imports.next_cursor}
                    prevCursor={imports.prev_cursor}
                />
            </div>
        </AuthenticatedLayout>
    );
}
