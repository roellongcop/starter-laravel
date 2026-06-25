import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminTeamCategory, type Crumb, type SelectOption } from '@/types';
import TeamCategoryForm from './Partials/TeamCategoryForm';

interface Props {
    category: AdminTeamCategory;
    organizations: SelectOption[];
}

export default function Show({ category, organizations }: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    const destroy = () =>
        router.delete(route('team-categories.destroy', category.token), {
            onFinish: () => setConfirmingDelete(false),
        });

    const breadcrumbs: Crumb[] = [
        { label: 'Team Categories', href: route('team-categories.index') },
        { label: category.name },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={category.name} />

            <PageHeader
                title={category.name}
                breadcrumbs={breadcrumbs}
                actions={
                    <>
                        <Can ability="team-categories.update">
                            <Button onClick={() => setEditOpen(true)}>
                                Edit
                            </Button>
                        </Can>
                        <Can ability="team-categories.delete">
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

            <Card>
                <CardContent className="space-y-4 pt-6">
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Organization
                        </span>
                        <p className="mt-1 text-sm">
                            {category.organization_name || '—'}
                        </p>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Description
                        </span>
                        <p className="mt-1 text-sm">
                            {category.description || '—'}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Sheet open={editOpen} onOpenChange={setEditOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>{`Edit ${category.name}`}</SheetTitle>
                        <SheetDescription>
                            A category used to classify teams.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <TeamCategoryForm
                            category={category}
                            organizations={organizations}
                            onSuccess={() => setEditOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <ConfirmDialog
                open={confirmingDelete}
                onOpenChange={setConfirmingDelete}
                title={`Delete ${category.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
