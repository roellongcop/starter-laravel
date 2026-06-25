import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import {
    Loader2,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
    Users,
} from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import TeamsPeopleTabs from '@/Components/TeamsPeopleTabs';
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
import {
    type AdminTeam,
    type OrgScopedOption,
    type SelectOption,
} from '@/types';
import TeamForm from './Partials/TeamForm';

interface Props {
    teams: { data: AdminTeam[] };
    filters: { search: string; organization: string; inactive: boolean };
    organizations: SelectOption[];
    categories: OrgScopedOption[];
    organizationRoles: OrgScopedOption[];
    users: SelectOption[];
}

export default function Index({
    teams,
    filters,
    organizations,
    categories,
    organizationRoles,
    users,
}: Props) {
    const f = useFilters<Props['filters']>({
        route: 'teams.index',
        reset: ['teams'],
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminTeam | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formTeam, setFormTeam] = useState<AdminTeam | null>(null);

    const openCreate = () => {
        setFormTeam(null);
        setFormOpen(true);
    };

    const openEdit = (team: AdminTeam) => {
        setFormTeam(team);
        setFormOpen(true);
    };

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('teams.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Teams & People" />

            <PageHeader
                title="Teams & People"
                description="Teams, categories and people across your organizations."
                actions={
                    <Can ability="teams.create">
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" /> New Team
                        </Button>
                    </Can>
                }
            />

            <TeamsPeopleTabs current="teams" />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <FilterBar onSubmit={f.submit}>
                    <FilterBar.Search
                        value={f.values.search}
                        onChange={(v) => f.set('search', v)}
                        placeholder="Search name or description…"
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

            {teams.data.length === 0 ? (
                <div className="rounded-lg border bg-card py-16 text-center text-sm text-muted-foreground">
                    No teams found.
                </div>
            ) : (
                <InfiniteScroll
                    data="teams"
                    buffer={300}
                    className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    loading={
                        <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin" />
                        </div>
                    }
                >
                    {teams.data.map((team) => (
                        <Card
                            key={team.token}
                            className="relative flex flex-col transition-shadow hover:shadow-md"
                        >
                            <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                <div className="space-y-1">
                                    <CardTitle className="flex items-center gap-2 text-base leading-tight">
                                        <Link
                                            href={route(
                                                'teams.show',
                                                team.token,
                                            )}
                                            className="after:absolute after:inset-0 focus-visible:outline-none"
                                        >
                                            {team.name}
                                        </Link>
                                    </CardTitle>
                                    <p className="text-sm text-muted-foreground">
                                        {team.organization_name ??
                                            'No organization'}
                                    </p>
                                </div>
                                <Can anyOf={['teams.update', 'teams.delete']}>
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
                                            <Can ability="teams.update">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        openEdit(team)
                                                    }
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                            </Can>
                                            <Can ability="teams.delete">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setDeleting(team)
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
                            <CardContent className="space-y-2">
                                <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                    <span>
                                        {team.team_category_name ?? '—'}
                                    </span>
                                    <span>·</span>
                                    <span>
                                        {team.organization_role_name ?? '—'}
                                    </span>
                                </div>
                                <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <Users className="h-4 w-4" />
                                    {team.members_count}{' '}
                                    {team.members_count === 1
                                        ? 'member'
                                        : 'members'}
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
                            {formTeam ? `Edit ${formTeam.name}` : 'New Team'}
                        </SheetTitle>
                        <SheetDescription>
                            A team belonging to an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <TeamForm
                            key={formTeam?.token ?? 'new'}
                            team={formTeam ?? undefined}
                            organizations={organizations}
                            categories={categories}
                            organizationRoles={organizationRoles}
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
