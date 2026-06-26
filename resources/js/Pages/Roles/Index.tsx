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
import { type AdminRole, type CursorResponse } from '@/types';

interface Props {
    roles: CursorResponse<AdminRole>;
    filters: { search: string };
}

export default function Index({ roles, filters }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'roles.index',
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminRole | null>(null);

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('roles.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Roles" />

            <PageHeader
                title="Roles"
                description="Roles bundle permissions; the granted permissions drive the sidebar and button visibility."
                actions={
                    <Can ability="roles.create">
                        <Button asChild>
                            <Link href={route('roles.create')}>
                                <Plus className="h-4 w-4" /> New Role
                            </Link>
                        </Button>
                    </Can>
                }
            />

            <FilterBar onSubmit={f.submit} className="mb-4">
                <FilterBar.Search
                    value={f.values.search}
                    onChange={(v) => f.set('search', v)}
                    placeholder="Search roles…"
                />
            </FilterBar>

            <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Permissions</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {roles.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="text-center text-muted-foreground"
                                >
                                    No roles found.
                                </TableCell>
                            </TableRow>
                        )}
                        {roles.data.map((role) => (
                            <TableRow key={role.token}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={route('roles.show', role.token)}
                                        className="hover:underline"
                                    >
                                        {role.name}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant={
                                            role.role_type === 'System'
                                                ? 'secondary'
                                                : 'outline'
                                        }
                                    >
                                        {role.role_type}
                                    </Badge>
                                </TableCell>
                                <TableCell
                                    className="max-w-sm truncate text-sm text-muted-foreground"
                                    title={role.description ?? undefined}
                                >
                                    {role.description || '—'}
                                </TableCell>
                                <TableCell>{role.permissions_count}</TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-1">
                                        <Can ability="roles.update">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Edit"
                                                aria-label="Edit"
                                                asChild
                                            >
                                                <Link
                                                    href={route(
                                                        'roles.edit',
                                                        role.token,
                                                    )}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </Can>
                                        {role.role_type !== 'System' && (
                                            <Can ability="roles.delete">
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    title="Delete"
                                                    aria-label="Delete"
                                                    onClick={() =>
                                                        setDeleting(role)
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
                    nextCursor={roles.next_cursor}
                    prevCursor={roles.prev_cursor}
                />
            </div>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
                title={`Delete ${deleting?.name}?`}
                description="Users assigned this role will lose its permissions."
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
