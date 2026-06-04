import { Head, router } from '@inertiajs/react';
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
import { type AdminVisitLog, type CursorResponse } from '@/types';

interface Props {
    logs: CursorResponse<AdminVisitLog>;
    filters: { search: string };
}

export default function Index({ logs, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(
            route('visit-logs.index'),
            { search },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Visit Logs" />
            <PageHeader
                title="Visit Logs"
                description="Per-page visitor activity."
            />

            <form onSubmit={submit} className="mb-4 flex gap-2">
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search URL…"
                    className="w-72"
                />
                <Button type="submit" variant="secondary">
                    Search
                </Button>
            </form>

            <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Visitor IP</TableHead>
                            <TableHead>Action</TableHead>
                            <TableHead>URL</TableHead>
                            <TableHead>When</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {logs.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="text-center text-muted-foreground"
                                >
                                    No visit logs.
                                </TableCell>
                            </TableRow>
                        )}
                        {logs.data.map((l) => (
                            <TableRow key={l.token}>
                                <TableCell className="font-mono text-sm">
                                    {l.visitor_ip ?? '—'}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">
                                        {l.action}
                                    </Badge>
                                </TableCell>
                                <TableCell className="break-all text-sm">
                                    {l.url}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {l.created_at
                                        ? new Date(
                                              l.created_at,
                                          ).toLocaleString()
                                        : '—'}
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
