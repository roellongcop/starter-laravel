import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Loader2, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
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

export default function Index({ organizations, filters, users }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'organizations.index',
        reset: ['organizations'],
        initial: filters,
    });
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
                    {organizations.data.map((organization) => (
                        <Card
                            key={organization.token}
                            className="relative flex h-full flex-col transition-shadow hover:shadow-md"
                        >
                            <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                <div className="min-w-0 space-y-1">
                                    <CardTitle className="text-base leading-tight">
                                        {/* Stretched link: the ::after overlay
                                            makes the whole card navigate to the
                                            show page, while the z-10 menu stays
                                            clickable above it. */}
                                        <Link
                                            href={route(
                                                'organizations.show',
                                                organization.token,
                                            )}
                                            className="line-clamp-1 after:absolute after:inset-0 focus-visible:outline-none"
                                        >
                                            {organization.name}
                                        </Link>
                                    </CardTitle>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {organization.point_of_contact_name ??
                                            'No point of contact'}
                                    </p>
                                </div>
                                <Can
                                    anyOf={[
                                        'organizations.update',
                                        'organizations.delete',
                                    ]}
                                >
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                className="relative z-10 shrink-0"
                                                aria-label="Actions"
                                            >
                                                <MoreHorizontal className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <Can ability="organizations.update">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        openEdit(organization)
                                                    }
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                            </Can>
                                            <Can ability="organizations.delete">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setDeleting(
                                                            organization,
                                                        )
                                                    }
                                                    className="text-destructive focus:text-destructive"
                                                >
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </Can>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </Can>
                            </CardHeader>
                            <CardContent className="flex flex-1 flex-col">
                                <p className="line-clamp-2 min-h-10 text-sm text-muted-foreground">
                                    {organization.description ?? '—'}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
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
