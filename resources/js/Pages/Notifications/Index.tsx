import { Head, router } from '@inertiajs/react';
import { Check, Trash2, Undo2 } from 'lucide-react';
import { useState } from 'react';

import CursorPager from '@/Components/CursorPager';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminNotification, type CursorResponse } from '@/types';

export default function Index({
    notifications,
}: {
    notifications: CursorResponse<AdminNotification>;
}) {
    const [selected, setSelected] = useState<string[]>([]);

    const toggle = (id: string) =>
        setSelected((s) =>
            s.includes(id) ? s.filter((x) => x !== id) : [...s, id],
        );

    const setRead = (id: string, read: boolean) =>
        router.patch(
            route('notifications.update', id),
            { read },
            { preserveScroll: true },
        );

    const bulk = (process: 'read' | 'unread' | 'delete') =>
        router.post(
            route('notifications.bulk'),
            { process, ids: selected },
            { preserveScroll: true, onFinish: () => setSelected([]) },
        );

    return (
        <AuthenticatedLayout>
            <Head title="Notifications" />

            <PageHeader
                title="Notifications"
                actions={
                    selected.length > 0 && (
                        <>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => bulk('read')}
                            >
                                Mark read
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => bulk('unread')}
                            >
                                Mark unread
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                onClick={() => bulk('delete')}
                            >
                                Delete
                            </Button>
                        </>
                    )
                }
            />

            <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-10"></TableHead>
                            <TableHead>Message</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {notifications.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="text-center text-muted-foreground"
                                >
                                    No notifications.
                                </TableCell>
                            </TableRow>
                        )}
                        {notifications.data.map((n) => (
                            <TableRow
                                key={n.id}
                                className={
                                    n.read ? 'text-muted-foreground' : ''
                                }
                            >
                                <TableCell>
                                    <Checkbox
                                        checked={selected.includes(n.id)}
                                        onCheckedChange={() => toggle(n.id)}
                                    />
                                </TableCell>
                                <TableCell
                                    className={n.read ? '' : 'font-medium'}
                                >
                                    {n.message}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">{n.type}</Badge>
                                </TableCell>
                                <TableCell>
                                    {n.read ? 'Read' : 'Unread'}
                                </TableCell>
                                <TableCell className="text-right">
                                    {n.read ? (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            title="Mark as unread"
                                            aria-label="Mark as unread"
                                            onClick={() => setRead(n.id, false)}
                                        >
                                            <Undo2 className="h-4 w-4" />
                                        </Button>
                                    ) : (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            title="Mark as read"
                                            aria-label="Mark as read"
                                            onClick={() => setRead(n.id, true)}
                                        >
                                            <Check className="h-4 w-4" />
                                        </Button>
                                    )}
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        title="Delete"
                                        aria-label="Delete"
                                        onClick={() =>
                                            router.post(
                                                route('notifications.bulk'),
                                                {
                                                    process: 'delete',
                                                    ids: [n.id],
                                                },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Trash2 className="h-4 w-4 text-destructive" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <CursorPager
                    nextCursor={notifications.next_cursor}
                    prevCursor={notifications.prev_cursor}
                />
            </div>
        </AuthenticatedLayout>
    );
}
