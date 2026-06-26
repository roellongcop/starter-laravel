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
import { type AdminOrganizationRole, type SelectOption } from '@/types';
import OrganizationRoleForm from './Partials/OrganizationRoleForm';

interface Props {
    roles: { data: AdminOrganizationRole[] };
    filters: { search: string; organization: string; inactive: boolean };
    organizations: SelectOption[];
}

export default function Index({ roles, filters, organizations }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'organization-roles.index',
        reset: ['roles'],
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminOrganizationRole | null>(
        null,
    );
    const [formOpen, setFormOpen] = useState(false);
    const [formRole, setFormRole] = useState<AdminOrganizationRole | null>(
        null,
    );

    const openCreate = () => {
        setFormRole(null);
        setFormOpen(true);
    };

    const openEdit = (role: AdminOrganizationRole) => {
        setFormRole(role);
        setFormOpen(true);
    };

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('organization-roles.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Organization Roles" />

            <PageHeader
                title="Organization Roles"
                description="Roles defined within each organization, used by teams and members."
                actions={
                    <Can ability="organization-roles.create">
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" /> New Role
                        </Button>
                    </Can>
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <FilterBar onSubmit={f.submit}>
                    <FilterBar.Search
                        value={f.values.search}
                        onChange={(v) => f.set('search', v)}
                        placeholder="Search roles…"
                    />
                    <FilterBar.Select
                        value={f.values.organization}
                        onChange={(v) => f.apply({ organization: v })}
                        options={organizations}
                        placeholder="All organizations"
                        allLabel="All organizations"
                        className="w-56"
                    />
                </FilterBar>
            </div>

            {roles.data.length === 0 ? (
                <div className="rounded-lg border bg-card py-16 text-center text-sm text-muted-foreground">
                    No organization roles found.
                </div>
            ) : (
                <InfiniteScroll
                    data="roles"
                    buffer={300}
                    className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    loading={
                        <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin" />
                        </div>
                    }
                >
                    {roles.data.map((role) => (
                        <Card
                            key={role.token}
                            className="relative flex h-full flex-col transition-shadow hover:shadow-md"
                        >
                            <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                <div className="min-w-0 space-y-1">
                                    <CardTitle className="flex min-w-0 items-center gap-2 text-base leading-tight">
                                        <Link
                                            href={route(
                                                'organization-roles.show',
                                                role.token,
                                            )}
                                            className="line-clamp-1 after:absolute after:inset-0 focus-visible:outline-none"
                                        >
                                            {role.name}
                                        </Link>
                                    </CardTitle>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {role.organization_name ??
                                            'No organization'}
                                    </p>
                                </div>
                                <Can
                                    anyOf={[
                                        'organization-roles.update',
                                        'organization-roles.delete',
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
                                            <Can ability="organization-roles.update">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        openEdit(role)
                                                    }
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                            </Can>
                                            <Can ability="organization-roles.delete">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setDeleting(role)
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
                                    {role.description || '—'}
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
                            {formRole ? `Edit ${formRole.name}` : 'New Role'}
                        </SheetTitle>
                        <SheetDescription>
                            A role defined within an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <OrganizationRoleForm
                            key={formRole?.token ?? 'new'}
                            role={formRole ?? undefined}
                            organizations={organizations}
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
