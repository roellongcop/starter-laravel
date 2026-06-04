import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

import CursorPager from '@/Components/CursorPager';
import PageHeader from '@/Components/PageHeader';
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
import { type AdminVisitor, type CursorResponse } from '@/types';

interface Props {
    visitors: CursorResponse<AdminVisitor>;
    filters: { search: string };
}

export default function Index({ visitors, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(
            route('visitors.index'),
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
            <Head title="Visitors" />
            <PageHeader
                title="Visitors"
                description="Tracked anonymous visitors."
            />

            <form onSubmit={submit} className="mb-4 flex gap-2">
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search IP / browser / OS…"
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
                            <TableHead>IP</TableHead>
                            <TableHead>Browser / OS</TableHead>
                            <TableHead>Device</TableHead>
                            <TableHead>Visits</TableHead>
                            <TableHead>Last visit</TableHead>
                            <TableHead className="text-right">View</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {visitors.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="text-center text-muted-foreground"
                                >
                                    No visitors.
                                </TableCell>
                            </TableRow>
                        )}
                        {visitors.data.map((v) => (
                            <TableRow key={v.token}>
                                <TableCell className="font-mono text-sm">
                                    {v.ip_address}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {v.browser} / {v.os}
                                </TableCell>
                                <TableCell>{v.device}</TableCell>
                                <TableCell>{v.visit_count}</TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {v.last_visit_at
                                        ? new Date(
                                              v.last_visit_at,
                                          ).toLocaleString()
                                        : '—'}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button size="sm" variant="ghost" asChild>
                                        <Link
                                            href={route(
                                                'visitors.show',
                                                v.token,
                                            )}
                                        >
                                            Logs
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
                    nextCursor={visitors.next_cursor}
                    prevCursor={visitors.prev_cursor}
                />
            </div>
        </AuthenticatedLayout>
    );
}
