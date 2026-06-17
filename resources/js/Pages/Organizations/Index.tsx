import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import CursorPager from '@/Components/CursorPager';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
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
import {
    type AdminOrganization,
    type CursorResponse,
    type SelectOption,
} from '@/types';
import OrganizationForm from './Partials/OrganizationForm';

interface Props {
    organizations: CursorResponse<AdminOrganization>;
    filters: { search: string; inactive: boolean };
    users: SelectOption[];
}

type BulkProcess = 'active' | 'in_active' | 'delete';

export default function Index({ organizations, filters, users }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'organizations.index',
        initial: filters,
    });
    const [selected, setSelected] = useState<string[]>([]);
    const [bulk, setBulk] = useState<BulkProcess | null>(null);
    const [deleting, setDeleting] = useState<AdminOrganization | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formOrganization, setFormOrganization] =
        useState<AdminOrganization | null>(null);

    const openCreate = () => {
        setFormOrganization(null);
        setFormOpen(true);
    };

    const openEdit = (organization: AdminOrganization) => {
        setFormOrganization(organization);
        setFormOpen(true);
    };

    const toggleRow = (id: string) =>
        setSelected((s) =>
            s.includes(id) ? s.filter((x) => x !== id) : [...s, id],
        );

    const runBulk = () => {
        if (!bulk) return;
        router.post(
            route('organizations.bulk'),
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
        router.delete(route('organizations.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Organizations" />

            <PageHeader
                title="Organizations"
                description="Organizations and their points of contact."
                actions={
                    <Can ability="organizations.create">
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" /> New Organization
                        </Button>
                    </Can>
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <FilterBar onSubmit={f.submit}>
                    <FilterBar.Search
                        value={f.values.search}
                        onChange={(v) => f.set('search', v)}
                        placeholder="Search name or description…"
                    />
                </FilterBar>

                {selected.length > 0 && (
                    <div className="ml-auto flex items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                            {selected.length} selected
                        </span>
                        <Can ability="organizations.update">
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setBulk('in_active')}
                            >
                                Inactivate
                            </Button>
                        </Can>
                        <Can ability="organizations.delete">
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
                            <TableHead>Name</TableHead>
                            <TableHead>Point of contact</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {organizations.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="text-center text-muted-foreground"
                                >
                                    No organizations found.
                                </TableCell>
                            </TableRow>
                        )}
                        {organizations.data.map((organization) => (
                            <TableRow key={organization.token}>
                                <TableCell>
                                    <Checkbox
                                        checked={selected.includes(
                                            organization.token,
                                        )}
                                        onCheckedChange={() =>
                                            toggleRow(organization.token)
                                        }
                                    />
                                </TableCell>
                                <TableCell className="font-medium">
                                    <Link
                                        href={route(
                                            'organizations.show',
                                            organization.token,
                                        )}
                                        className="hover:underline"
                                    >
                                        {organization.name}
                                    </Link>
                                </TableCell>
                                <TableCell className="text-sm">
                                    {organization.point_of_contact_name ?? '—'}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {organization.description ?? '—'}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-1">
                                        <Can ability="organizations.update">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Edit"
                                                aria-label="Edit"
                                                onClick={() =>
                                                    openEdit(organization)
                                                }
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                        </Can>
                                        <Can ability="organizations.delete">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Delete"
                                                aria-label="Delete"
                                                onClick={() =>
                                                    setDeleting(organization)
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

            <div className="mt-4">
                <CursorPager
                    nextCursor={organizations.next_cursor}
                    prevCursor={organizations.prev_cursor}
                />
            </div>

            <Sheet open={formOpen} onOpenChange={setFormOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>
                            {formOrganization
                                ? `Edit ${formOrganization.name}`
                                : 'New Organization'}
                        </SheetTitle>
                        <SheetDescription>
                            An organization and its point of contact.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <OrganizationForm
                            key={formOrganization?.token ?? 'new'}
                            organization={formOrganization ?? undefined}
                            users={users}
                            onSuccess={() => setFormOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <ConfirmDialog
                open={bulk !== null}
                onOpenChange={(o) => !o && setBulk(null)}
                title={`Apply "${bulk}" to ${selected.length} organization(s)?`}
                confirmLabel="Apply"
                destructive={bulk === 'delete'}
                onConfirm={runBulk}
            />

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
                title={`Delete ${deleting?.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
