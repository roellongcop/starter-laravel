import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import CursorPager from '@/Components/CursorPager';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
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
import { type AdminIp, type CursorResponse } from '@/types';

interface Props {
    ips: CursorResponse<AdminIp>;
    filters: { search: string; inactive: boolean };
    can: {
        create: boolean;
        update: boolean;
        delete: boolean;
        viewInactive: boolean;
    };
}

type BulkProcess = 'active' | 'in_active' | 'delete' | 'white_list';

export default function Index({ ips, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [selected, setSelected] = useState<number[]>([]);
    const [bulk, setBulk] = useState<BulkProcess | null>(null);
    const [deleting, setDeleting] = useState<AdminIp | null>(null);

    const reload = (params: Record<string, string | number | undefined>) =>
        router.get(route('ips.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        reload({ search, inactive: filters.inactive ? 1 : undefined });
    };

    const toggleRow = (id: number) =>
        setSelected((s) =>
            s.includes(id) ? s.filter((x) => x !== id) : [...s, id],
        );

    const runBulk = () => {
        if (!bulk) return;
        router.post(
            route('ips.bulk'),
            { process: bulk, ids: selected },
            {
                preserveScroll: true,
                onFinish: () => {
                    setBulk(null);
                    setSelected([]);
                },
            },
        );
    };

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('ips.destroy', deleting.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout header="IP Lists">
            <Head title="IP Lists" />

            <PageHeader
                title="IP Lists"
                description="Whitelist / blacklist entries enforced by the IP middleware."
                actions={
                    <Can ability="ips.create">
                        <Button asChild>
                            <Link href={route('ips.create')}>
                                <Plus className="h-4 w-4" /> New Entry
                            </Link>
                        </Button>
                    </Can>
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <form onSubmit={submitSearch} className="flex gap-2">
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search IP or description…"
                        className="w-72"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                {selected.length > 0 && (
                    <div className="ml-auto flex items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                            {selected.length} selected
                        </span>
                        <Can ability="ips.update">
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setBulk('white_list')}
                            >
                                Whitelist
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setBulk('in_active')}
                            >
                                Inactivate
                            </Button>
                        </Can>
                        <Can ability="ips.delete">
                            <Button
                                size="sm"
                                variant="destructive"
                                onClick={() => setBulk('delete')}
                            >
                                Delete
                            </Button>
                        </Can>
                    </div>
                )}
            </div>

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-10"></TableHead>
                            <TableHead>IP address</TableHead>
                            <TableHead>List</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {ips.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="text-center text-muted-foreground"
                                >
                                    No IP entries found.
                                </TableCell>
                            </TableRow>
                        )}
                        {ips.data.map((ip) => (
                            <TableRow key={ip.id}>
                                <TableCell>
                                    <Checkbox
                                        checked={selected.includes(ip.id)}
                                        onCheckedChange={() => toggleRow(ip.id)}
                                    />
                                </TableCell>
                                <TableCell className="font-mono text-sm">
                                    <Link
                                        href={route('ips.show', ip.id)}
                                        className="hover:underline"
                                    >
                                        {ip.ip_address}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant={
                                            ip.list_type === 'Whitelist'
                                                ? 'default'
                                                : 'destructive'
                                        }
                                    >
                                        {ip.list_type}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {ip.description ?? '—'}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-1">
                                        <Can ability="ips.update">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                asChild
                                            >
                                                <Link
                                                    href={route(
                                                        'ips.edit',
                                                        ip.id,
                                                    )}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </Can>
                                        <Can ability="ips.delete">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                onClick={() => setDeleting(ip)}
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
                    nextCursor={ips.next_cursor}
                    prevCursor={ips.prev_cursor}
                />
            </div>

            <ConfirmDialog
                open={bulk !== null}
                onOpenChange={(o) => !o && setBulk(null)}
                title={`Apply "${bulk}" to ${selected.length} entrie(s)?`}
                confirmLabel="Apply"
                destructive={bulk === 'delete'}
                onConfirm={runBulk}
            />

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
                title={`Delete ${deleting?.ip_address}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
