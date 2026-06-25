import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { Loader2, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
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
import { type AdminTeamCategory, type SelectOption } from '@/types';
import TeamCategoryForm from './Partials/TeamCategoryForm';

interface Props {
    categories: { data: AdminTeamCategory[] };
    filters: { search: string; organization: string; inactive: boolean };
    organizations: SelectOption[];
}

export default function Index({ categories, filters, organizations }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'team-categories.index',
        reset: ['categories'],
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminTeamCategory | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formCategory, setFormCategory] = useState<AdminTeamCategory | null>(
        null,
    );

    const openCreate = () => {
        setFormCategory(null);
        setFormOpen(true);
    };

    const openEdit = (category: AdminTeamCategory) => {
        setFormCategory(category);
        setFormOpen(true);
    };

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('team-categories.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Teams & People" />

            <PageHeader
                title="Teams & People"
                description="Categories used to classify teams, grouped by organization."
                actions={
                    <Can ability="team-categories.create">
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" /> New Category
                        </Button>
                    </Can>
                }
            />

            <TeamsPeopleTabs current="categories" />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <FilterBar onSubmit={f.submit}>
                    <FilterBar.Search
                        value={f.values.search}
                        onChange={(v) => f.set('search', v)}
                        placeholder="Search categories…"
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

            {categories.data.length === 0 ? (
                <div className="rounded-lg border bg-card py-16 text-center text-sm text-muted-foreground">
                    No categories found.
                </div>
            ) : (
                <InfiniteScroll
                    data="categories"
                    buffer={300}
                    className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    loading={
                        <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin" />
                        </div>
                    }
                >
                    {categories.data.map((category) => (
                        <Card
                            key={category.token}
                            className="relative flex flex-col transition-shadow hover:shadow-md"
                        >
                            <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                <div className="space-y-1">
                                    <CardTitle className="flex items-center gap-2 text-base leading-tight">
                                        <Link
                                            href={route(
                                                'team-categories.show',
                                                category.token,
                                            )}
                                            className="after:absolute after:inset-0 hover:underline focus-visible:outline-none"
                                        >
                                            {category.name}
                                        </Link>
                                    </CardTitle>
                                    <p className="text-sm text-muted-foreground">
                                        {category.organization_name ??
                                            'No organization'}
                                    </p>
                                </div>
                                <Can
                                    anyOf={[
                                        'team-categories.update',
                                        'team-categories.delete',
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
                                            <Can ability="team-categories.update">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        openEdit(category)
                                                    }
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                            </Can>
                                            <Can ability="team-categories.delete">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setDeleting(category)
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
                            <CardContent>
                                <p className="line-clamp-2 text-sm text-muted-foreground">
                                    {category.description || '—'}
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
                            {formCategory
                                ? `Edit ${formCategory.name}`
                                : 'New Category'}
                        </SheetTitle>
                        <SheetDescription>
                            A category used to classify teams.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <TeamCategoryForm
                            key={formCategory?.token ?? 'new'}
                            category={formCategory ?? undefined}
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
