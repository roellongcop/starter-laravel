import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    type AdminTeam,
    type Crumb,
    type OrgScopedOption,
    type SelectOption,
} from '@/types';
import TeamForm from './Partials/TeamForm';

interface Props {
    team: AdminTeam;
    organizations: SelectOption[];
    categories: OrgScopedOption[];
    organizationRoles: OrgScopedOption[];
    users: SelectOption[];
}

export default function Show({
    team,
    organizations,
    categories,
    organizationRoles,
    users,
}: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    const destroy = () =>
        router.delete(route('teams.destroy', team.token), {
            onFinish: () => setConfirmingDelete(false),
        });

    const breadcrumbs: Crumb[] = [
        { label: 'Teams', href: route('teams.index') },
        { label: team.name },
    ];

    const roster = team.roster ?? [];

    return (
        <AuthenticatedLayout>
            <Head title={team.name} />

            <PageHeader
                title={team.name}
                breadcrumbs={breadcrumbs}
                actions={
                    <>
                        <Can ability="teams.update">
                            <Button onClick={() => setEditOpen(true)}>
                                Edit
                            </Button>
                        </Can>
                        <Can ability="teams.delete">
                            <Button
                                variant="destructive"
                                onClick={() => setConfirmingDelete(true)}
                            >
                                Delete
                            </Button>
                        </Can>
                    </>
                }
            />

            <div className="space-y-4">
                <Card>
                    <CardContent className="grid gap-4 pt-6 sm:grid-cols-2">
                        <div>
                            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                Organization
                            </span>
                            <p className="mt-1 text-sm">
                                {team.organization_name || '—'}
                            </p>
                        </div>
                        <div>
                            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                Category
                            </span>
                            <p className="mt-1 text-sm">
                                {team.team_category_name || '—'}
                            </p>
                        </div>
                        <div>
                            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                Role
                            </span>
                            <p className="mt-1 text-sm">
                                {team.organization_role_name || '—'}
                            </p>
                        </div>
                        <div className="sm:col-span-2">
                            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                Description
                            </span>
                            <p className="mt-1 text-sm">
                                {team.description || '—'}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Members ({roster.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {roster.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                No members yet.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Role</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {roster.map((member) => (
                                        <TableRow key={member.token}>
                                            <TableCell>{member.name}</TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {member.role}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Sheet open={editOpen} onOpenChange={setEditOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>{`Edit ${team.name}`}</SheetTitle>
                        <SheetDescription>
                            A team belonging to an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <TeamForm
                            team={team}
                            organizations={organizations}
                            categories={categories}
                            organizationRoles={organizationRoles}
                            users={users}
                            onSuccess={() => setEditOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <ConfirmDialog
                open={confirmingDelete}
                onOpenChange={setConfirmingDelete}
                title={`Delete ${team.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
