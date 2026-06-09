import { Head } from '@inertiajs/react';

import CursorPager from '@/Components/CursorPager';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminLoginHistory, type CursorResponse } from '@/types';

interface Props {
    history: CursorResponse<AdminLoginHistory>;
    filters: { search: string; event: string };
}

const eventColor: Record<string, 'default' | 'secondary'> = {
    login: 'default',
    logout: 'secondary',
};

export default function Index({ history, filters }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'login-history.index',
        initial: filters,
    });

    return (
        <AuthenticatedLayout>
            <Head title="Login History" />
            <PageHeader
                title="Login History"
                description="Read-only record of user sign-ins and sign-outs."
            />

            <FilterBar onSubmit={f.submit} className="mb-4">
                <FilterBar.Search
                    value={f.values.search}
                    onChange={(v) => f.set('search', v)}
                    placeholder="Search user or IP"
                />
            </FilterBar>

            <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Event</TableHead>
                            <TableHead>User</TableHead>
                            <TableHead>IP</TableHead>
                            <TableHead>Browser / OS</TableHead>
                            <TableHead>Device</TableHead>
                            <TableHead>When</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {history.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="text-center text-muted-foreground"
                                >
                                    No login history yet.
                                </TableCell>
                            </TableRow>
                        )}
                        {history.data.map((h) => (
                            <TableRow key={h.id}>
                                <TableCell>
                                    <Badge
                                        variant={
                                            eventColor[h.event] ?? 'outline'
                                        }
                                    >
                                        {h.event}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-sm">
                                    {h.user}
                                    {h.email && (
                                        <span className="block text-xs text-muted-foreground">
                                            {h.email}
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {h.ip_address ?? '—'}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {h.browser} / {h.os}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {h.device}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {h.created_at
                                        ? new Date(
                                              h.created_at,
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
                    nextCursor={history.next_cursor}
                    prevCursor={history.prev_cursor}
                />
            </div>
        </AuthenticatedLayout>
    );
}
