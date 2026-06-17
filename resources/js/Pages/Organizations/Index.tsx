import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Loader2, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
import { useFilters } from '@/hooks/use-filters';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminOrganization, type SelectOption } from '@/types';
import OrganizationForm from './Partials/OrganizationForm';

interface Props {
    // Serialized CursorPaginator wrapped by Inertia::scroll(); the
    // <InfiniteScroll> component appends pages into `data` as the user scrolls.
    organizations: { data: AdminOrganization[] };
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

            {organizations.data.length === 0 ? (
                <div className="rounded-lg border bg-card py-16 text-center text-sm text-muted-foreground">
                    No organizations found.
                </div>
            ) : (
                <InfiniteScroll
                    data="organizations"
                    buffer={300}
                    className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    loading={
                        <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin" />
                        </div>
                    }
                >
                    {organizations.data.map((organization) => {
                        const isSelected = selected.includes(
                            organization.token,
                        );
                        return (
                            <Card
                                key={organization.token}
                                data-selected={isSelected}
                                className="relative flex flex-col transition-shadow focus-within:ring-2 focus-within:ring-ring hover:shadow-md data-[selected=true]:ring-2 data-[selected=true]:ring-primary"
                            >
                                <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                    <div className="flex items-start gap-3">
                                        <Checkbox
                                            className="relative z-10 mt-1"
                                            checked={isSelected}
                                            onCheckedChange={() =>
                                                toggleRow(organization.token)
                                            }
                                            aria-label={`Select ${organization.name}`}
                                        />
                                        <div className="space-y-1">
                                            <CardTitle className="text-base leading-tight">
                                                {/* Stretched link: the ::after
                                                    overlay makes the whole card
                                                    navigate to the show page,
                                                    while z-10 controls stay
                                                    clickable above it. */}
                                                <Link
                                                    href={route(
                                                        'organizations.show',
                                                        organization.token,
                                                    )}
                                                    className="after:absolute after:inset-0 hover:underline focus-visible:outline-none"
                                                >
                                                    {organization.name}
                                                </Link>
                                            </CardTitle>
                                            <p className="text-sm text-muted-foreground">
                                                {organization.point_of_contact_name ??
                                                    'No point of contact'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="relative z-10 flex shrink-0 gap-1">
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
                                </CardHeader>
                                <CardContent>
                                    <p className="line-clamp-3 text-sm text-muted-foreground">
                                        {organization.description ?? '—'}
                                    </p>
                                </CardContent>
                            </Card>
                        );
                    })}
                </InfiniteScroll>
            )}

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
