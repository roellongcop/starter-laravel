import { Head, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

import ConfirmDialog from '@/Components/ConfirmDialog';
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
import { type AdminSession, type CursorResponse } from '@/types';

interface Props {
    sessions: CursorResponse<AdminSession>;
    filters: { search: string };
    can: { delete: boolean };
}

export default function Index({ sessions, filters, can }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [revoking, setRevoking] = useState<AdminSession | null>(null);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(
            route('sessions.index'),
            { search },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const revoke = () => {
        if (!revoking) return;
        router.delete(route('sessions.destroy', revoking.id), {
            preserveScroll: true,
            onFinish: () => setRevoking(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Sessions" />
            <PageHeader
                title="Sessions"
                description="Active database sessions."
            />

            <form onSubmit={submitSearch} className="mb-4 flex gap-2">
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search user or IP…"
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
                            <TableHead>User</TableHead>
                            <TableHead>IP</TableHead>
                            <TableHead>Browser</TableHead>
                            <TableHead>Last activity</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {sessions.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="text-center text-muted-foreground"
                                >
                                    No sessions.
                                </TableCell>
                            </TableRow>
                        )}
                        {sessions.data.map((s) => (
                            <TableRow key={s.id}>
                                <TableCell>
                                    {s.user ?? 'Guest'}
                                    {s.is_current && (
                                        <Badge
                                            className="ml-2"
                                            variant="secondary"
                                        >
                                            current
                                        </Badge>
                                    )}
                                </TableCell>
                                <TableCell className="font-mono text-sm">
                                    {s.ip_address}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {s.browser} / {s.os}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {s.last_activity
                                        ? new Date(
                                              s.last_activity,
                                          ).toLocaleString()
                                        : '—'}
                                </TableCell>
                                <TableCell className="text-right">
                                    {can.delete && !s.is_current && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => setRevoking(s)}
                                        >
                                            Revoke
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
                    nextCursor={sessions.next_cursor}
                    prevCursor={sessions.prev_cursor}
                />
            </div>

            <ConfirmDialog
                open={revoking !== null}
                onOpenChange={(o) => !o && setRevoking(null)}
                title="Revoke this session?"
                description="The user will be logged out on that device."
                confirmLabel="Revoke"
                destructive
                onConfirm={revoke}
            />
        </AuthenticatedLayout>
    );
}
