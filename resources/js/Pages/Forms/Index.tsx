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
import { useFilters } from '@/hooks/use-filters';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminForm, type SelectOption } from '@/types';

interface Props {
    forms: { data: AdminForm[] };
    filters: { search: string; organization: string; inactive: boolean };
    organizations: SelectOption[];
}

export default function Index({ forms, filters, organizations }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'forms.index',
        reset: ['forms'],
        initial: filters,
    });
    const [deleting, setDeleting] = useState<AdminForm | null>(null);

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('forms.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Forms" />

            <PageHeader
                title="Forms"
                description="Build forms and collect responses."
                actions={
                    <Can ability="forms.create">
                        <Button asChild>
                            <Link href={route('forms.create')}>
                                <Plus className="h-4 w-4" /> New Form
                            </Link>
                        </Button>
                    </Can>
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <FilterBar onSubmit={f.submit}>
                    <FilterBar.Search
                        value={f.values.search}
                        onChange={(v) => f.set('search', v)}
                        placeholder="Search title or description…"
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

            {forms.data.length === 0 ? (
                <div className="rounded-lg border bg-card py-16 text-center text-sm text-muted-foreground">
                    No forms found.
                </div>
            ) : (
                <InfiniteScroll
                    data="forms"
                    buffer={300}
                    className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    loading={
                        <div className="col-span-full flex justify-center py-6 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin" />
                        </div>
                    }
                >
                    {forms.data.map((form) => (
                        <Card
                            key={form.token}
                            className="relative flex flex-col transition-shadow hover:shadow-md"
                        >
                            <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                                <div className="space-y-1">
                                    <CardTitle className="text-base leading-tight">
                                        <Link
                                            href={route(
                                                'forms.show',
                                                form.token,
                                            )}
                                            className="after:absolute after:inset-0 hover:underline focus-visible:outline-none"
                                        >
                                            {form.title}
                                        </Link>
                                    </CardTitle>
                                    <p className="text-sm text-muted-foreground">
                                        {form.organization_name ??
                                            'No organization'}
                                    </p>
                                </div>
                                <Can anyOf={['forms.update', 'forms.delete']}>
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
                                            <Can ability="forms.update">
                                                <DropdownMenuItem asChild>
                                                    <Link
                                                        href={route(
                                                            'forms.edit',
                                                            form.token,
                                                        )}
                                                    >
                                                        <Pencil className="mr-2 h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                            </Can>
                                            <Can ability="forms.delete">
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setDeleting(form)
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
                                    {form.description ?? '—'}
                                </p>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {form.form_fields.length} field
                                    {form.form_fields.length === 1 ? '' : 's'}
                                    {typeof form.responses_count === 'number' &&
                                        ` · ${form.responses_count} response${form.responses_count === 1 ? '' : 's'}`}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </InfiniteScroll>
            )}

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
                title={`Delete ${deleting?.title}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
