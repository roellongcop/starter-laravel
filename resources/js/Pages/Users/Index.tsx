import { Head, Link, router } from '@inertiajs/react';
import { Columns3, Download, Pencil, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import Avatar from '@/Components/Avatar';
import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import CursorPager from '@/Components/CursorPager';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
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
import { type AdminUser, type CursorResponse } from '@/types';

interface Props {
    users: CursorResponse<AdminUser>;
    filters: {
        search: string;
        inactive: boolean;
        date_from: string;
        date_to: string;
    };
    can: {
        create: boolean;
        update: boolean;
        delete: boolean;
        viewInactive: boolean;
        export: boolean;
    };
    exportFormats: string[];
}

type BulkProcess = 'active' | 'in_active' | 'delete';
type Column = 'email' | 'status' | 'roles';

const COLUMN_KEY = 'users.columns';

export default function Index({ users, filters, can, exportFormats }: Props) {
    const [search, setSearch] = useState(filters.search);
    // const [dateFrom, setDateFrom] = useState(filters.date_from);
    // const [dateTo, setDateTo] = useState(filters.date_to);
    const [selected, setSelected] = useState<number[]>([]);
    const [bulk, setBulk] = useState<BulkProcess | null>(null);
    const [deleting, setDeleting] = useState<AdminUser | null>(null);
    // Read saved visibility in the initializer (not a useEffect) so the first
    // paint already reflects the user's choice — otherwise a hidden column
    // flashes visible for one frame before the effect hides it.
    const [columns, setColumns] = useState<Record<Column, boolean>>(() => {
        const base: Record<Column, boolean> = {
            email: true,
            status: true,
            roles: true,
        };
        try {
            const saved = localStorage.getItem(COLUMN_KEY);
            return saved ? { ...base, ...JSON.parse(saved) } : base;
        } catch {
            return base;
        }
    });

    const setColumn = (key: Column, value: boolean) =>
        setColumns((c) => {
            const next = { ...c, [key]: value };
            localStorage.setItem(COLUMN_KEY, JSON.stringify(next));
            return next;
        });

    const reload = (params: Record<string, string | number | undefined>) =>
        router.get(route('users.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });

    const currentFilters = () => ({
        search,
        inactive: filters.inactive ? 1 : undefined,
        // date_from: dateFrom || undefined,
        // date_to: dateTo || undefined,
    });

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        reload(currentFilters());
    };

    const toggleInactive = (checked: boolean) =>
        reload({ ...currentFilters(), inactive: checked ? 1 : undefined });

    const toggleRow = (id: number) =>
        setSelected((s) =>
            s.includes(id) ? s.filter((x) => x !== id) : [...s, id],
        );

    const runBulk = () => {
        if (!bulk) return;
        router.post(
            route('users.bulk'),
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
        router.delete(route('users.destroy', deleting.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    const exportAs = (format: string) =>
        router.post(route('exports.store'), {
            format,
            resource: 'users',
            filters: {
                search,
                // date_from: dateFrom,
                // date_to: dateTo
            },
        });

    const visibleColumnCount = Object.values(columns).filter(Boolean).length;

    return (
        <AuthenticatedLayout>
            <Head title="Users" />

            <PageHeader
                title="Users"
                description="Manage user accounts, roles and status."
                actions={
                    <Can ability="users.create">
                        <Button asChild>
                            <Link href={route('users.create')}>
                                <Plus className="h-4 w-4" /> New User
                            </Link>
                        </Button>
                    </Can>
                }
            />

            <div className="mb-4 flex flex-wrap gap-3">
                <form
                    onSubmit={submitSearch}
                    className="flex flex-wrap items-end gap-2"
                >
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search name, email, username…"
                        className="w-64"
                    />
                    {/*<Input*/}
                    {/*    type="date"*/}
                    {/*    value={dateFrom}*/}
                    {/*    onChange={(e) => setDateFrom(e.target.value)}*/}
                    {/*    className="w-40"*/}
                    {/*    aria-label="Created from"*/}
                    {/*/>*/}
                    {/*<Input*/}
                    {/*    type="date"*/}
                    {/*    value={dateTo}*/}
                    {/*    onChange={(e) => setDateTo(e.target.value)}*/}
                    {/*    className="w-40"*/}
                    {/*    aria-label="Created to"*/}
                    {/*/>*/}
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                {can.viewInactive && (
                    <label className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Checkbox
                            checked={filters.inactive}
                            onCheckedChange={(c) => toggleInactive(Boolean(c))}
                        />
                        Show inactive
                    </label>
                )}

                <div className="ml-auto flex items-center gap-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" size="sm">
                                <Columns3 className="h-4 w-4" /> Columns
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuLabel>Show columns</DropdownMenuLabel>
                            {(['email', 'status', 'roles'] as Column[]).map(
                                (c) => (
                                    <DropdownMenuCheckboxItem
                                        key={c}
                                        checked={columns[c]}
                                        onCheckedChange={(v) =>
                                            setColumn(c, Boolean(v))
                                        }
                                        className="capitalize"
                                    >
                                        {c}
                                    </DropdownMenuCheckboxItem>
                                ),
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>

                    {can.export && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm">
                                    <Download className="h-4 w-4" /> Export
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuLabel>
                                    Export current filter
                                </DropdownMenuLabel>
                                {exportFormats.map((f) => (
                                    <DropdownMenuItem
                                        key={f}
                                        onClick={() => exportAs(f)}
                                        className="uppercase"
                                    >
                                        {f}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </div>

            {selected.length > 0 && (
                <div className="mb-3 flex items-center gap-2">
                    <span className="text-sm text-muted-foreground">
                        {selected.length} selected
                    </span>
                    <Can ability="users.update">
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setBulk('active')}
                        >
                            Activate
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setBulk('in_active')}
                        >
                            Inactivate
                        </Button>
                    </Can>
                    <Can ability="users.delete">
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

            <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-10"></TableHead>
                            <TableHead>Name</TableHead>
                            {columns.email && <TableHead>Email</TableHead>}
                            {columns.status && <TableHead>Status</TableHead>}
                            {columns.roles && <TableHead>Roles</TableHead>}
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {users.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={3 + visibleColumnCount}
                                    className="text-center text-muted-foreground"
                                >
                                    No users found.
                                </TableCell>
                            </TableRow>
                        )}
                        {users.data.map((user) => (
                            <TableRow key={user.id}>
                                <TableCell>
                                    <Checkbox
                                        checked={selected.includes(user.id)}
                                        onCheckedChange={() =>
                                            toggleRow(user.id)
                                        }
                                    />
                                </TableCell>
                                <TableCell className="font-medium">
                                    <div className="flex items-center gap-2">
                                        <Avatar
                                            name={user.name}
                                            src={user.avatar_url}
                                            size={28}
                                        />
                                        <Link
                                            href={route('users.show', user.id)}
                                            className="hover:underline"
                                        >
                                            {user.name}
                                        </Link>
                                        {user.username && (
                                            <span className="text-xs text-muted-foreground">
                                                @{user.username}
                                            </span>
                                        )}
                                    </div>
                                </TableCell>
                                {columns.email && (
                                    <TableCell>{user.email}</TableCell>
                                )}
                                {columns.status && (
                                    <TableCell>
                                        <Badge
                                            variant={
                                                user.user_status === 'Active'
                                                    ? 'default'
                                                    : user.user_status ===
                                                        'Blocked'
                                                      ? 'destructive'
                                                      : 'secondary'
                                            }
                                        >
                                            {user.user_status}
                                        </Badge>
                                    </TableCell>
                                )}
                                {columns.roles && (
                                    <TableCell className="text-sm text-muted-foreground">
                                        {user.roles.join(', ') || '—'}
                                    </TableCell>
                                )}
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-1">
                                        <Can ability="users.update">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                asChild
                                            >
                                                <Link
                                                    href={route(
                                                        'users.edit',
                                                        user.id,
                                                    )}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </Can>
                                        <Can ability="users.delete">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                onClick={() =>
                                                    setDeleting(user)
                                                }
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

            <div className="mt-4 flex items-center justify-between">
                {users.total !== undefined && (
                    <span className="text-sm text-muted-foreground">
                        {users.total.toLocaleString()}{' '}
                        {users.total <= 1 ? 'record' : 'records'} found
                    </span>
                )}
                <CursorPager
                    nextCursor={users.next_cursor}
                    prevCursor={users.prev_cursor}
                />
            </div>

            <ConfirmDialog
                open={bulk !== null}
                onOpenChange={(o) => !o && setBulk(null)}
                title={`${bulk === 'delete' ? 'Delete' : bulk === 'active' ? 'Activate' : 'Inactivate'} ${selected.length} user(s)?`}
                description="This applies to all selected users."
                confirmLabel="Apply"
                destructive={bulk === 'delete'}
                onConfirm={runBulk}
            />

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
                title={`Delete ${deleting?.name}?`}
                description="This permanently removes the user."
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
