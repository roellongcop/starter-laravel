import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import CursorPager from '@/Components/CursorPager';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
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
import { type AdminIp, type CursorResponse, type SelectOption } from '@/types';
import IpForm from './Partials/IpForm';

interface Props {
    ips: CursorResponse<AdminIp>;
    filters: { search: string; inactive: boolean };
    listTypes: SelectOption[];
}

type BulkProcess = 'active' | 'in_active' | 'delete' | 'white_list';

export default function Index({ ips, filters, listTypes }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'ips.index',
        initial: filters,
    });
    const [selected, setSelected] = useState<string[]>([]);
    const [bulk, setBulk] = useState<BulkProcess | null>(null);
    const [deleting, setDeleting] = useState<AdminIp | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formIp, setFormIp] = useState<AdminIp | null>(null);
    // Bumped on every open so the form's key changes and it remounts with the
    // fresh record — re-editing the same row otherwise reuses a stale instance.
    const [formNonce, setFormNonce] = useState(0);

    const openCreate = () => {
        setFormIp(null);
        setFormNonce((n) => n + 1);
        setFormOpen(true);
    };

    const openEdit = (ip: AdminIp) => {
        setFormIp(ip);
        setFormNonce((n) => n + 1);
        setFormOpen(true);
    };

    const toggleRow = (id: string) =>
        setSelected((s) =>
            s.includes(id) ? s.filter((x) => x !== id) : [...s, id],
        );

    const runBulk = () => {
        if (!bulk) return;
        router.post(
            route('ips.bulk'),
            { process: bulk, tokens: selected },
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
        router.delete(route('ips.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="IP Lists" />

            <PageHeader
                title="IP Lists"
                description="Whitelist / blacklist entries enforced by the IP middleware."
                actions={
                    <Can ability="ips.create">
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" /> New Entry
                        </Button>
                    </Can>
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <FilterBar onSubmit={f.submit}>
                    <FilterBar.Search
                        value={f.values.search}
                        onChange={(v) => f.set('search', v)}
                        placeholder="Search IP or description…"
                    />
                </FilterBar>

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

            <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
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
                            <TableRow key={ip.token}>
                                <TableCell>
                                    <Checkbox
                                        checked={selected.includes(ip.token)}
                                        onCheckedChange={() =>
                                            toggleRow(ip.token)
                                        }
                                    />
                                </TableCell>
                                <TableCell className="font-mono text-sm">
                                    <Link
                                        href={route('ips.show', ip.token)}
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
                                <TableCell
                                    className="max-w-sm truncate text-sm text-muted-foreground"
                                    title={ip.description ?? undefined}
                                >
                                    {ip.description ?? '—'}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-1">
                                        <Can ability="ips.update">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Edit"
                                                aria-label="Edit"
                                                onClick={() => openEdit(ip)}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                        </Can>
                                        <Can ability="ips.delete">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Delete"
                                                aria-label="Delete"
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

            <Sheet open={formOpen} onOpenChange={setFormOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>
                            {formIp
                                ? `Edit ${formIp.ip_address}`
                                : 'New IP Entry'}
                        </SheetTitle>
                        <SheetDescription>
                            Whitelist or blacklist entry enforced by the IP
                            middleware.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <IpForm
                            key={`${formIp?.token ?? 'new'}-${formNonce}`}
                            ip={formIp ?? undefined}
                            listTypes={listTypes}
                            onSuccess={() => setFormOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

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
