import { Head } from '@inertiajs/react';

import CursorPager from '@/Components/CursorPager';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import TeamsPeopleTabs from '@/Components/TeamsPeopleTabs';
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
    type AdminPerson,
    type CursorResponse,
    type SelectOption,
} from '@/types';

interface Props {
    people: CursorResponse<AdminPerson>;
    filters: { search: string; organization: string; inactive: boolean };
    organizations: SelectOption[];
}

export default function Index({ people, filters, organizations }: Props) {
    const f = useFilters<Props['filters']>({
        route: 'people.index',
        initial: filters,
    });

    return (
        <AuthenticatedLayout>
            <Head title="Teams & People" />

            <PageHeader
                title="Teams & People"
                description="People assigned to teams across your organizations."
            />

            <TeamsPeopleTabs current="people" />

            <FilterBar onSubmit={f.submit} className="mb-4">
                <FilterBar.Search
                    value={f.values.search}
                    onChange={(v) => f.set('search', v)}
                    placeholder="Search by member name or email…"
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

            <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Team</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Organization</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {people.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="text-center text-muted-foreground"
                                >
                                    No people found.
                                </TableCell>
                            </TableRow>
                        )}
                        {people.data.map((person) => (
                            <TableRow key={person.token}>
                                <TableCell
                                    className="max-w-[12rem] truncate text-sm font-medium"
                                    title={person.name}
                                >
                                    {person.name}
                                </TableCell>
                                <TableCell
                                    className="max-w-[12rem] truncate text-sm text-muted-foreground"
                                    title={person.team}
                                >
                                    {person.team}
                                </TableCell>
                                <TableCell
                                    className="max-w-[12rem] truncate text-sm text-muted-foreground"
                                    title={person.role}
                                >
                                    {person.role}
                                </TableCell>
                                <TableCell
                                    className="max-w-[12rem] truncate text-sm text-muted-foreground"
                                    title={person.organization}
                                >
                                    {person.organization}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <CursorPager
                    nextCursor={people.next_cursor}
                    prevCursor={people.prev_cursor}
                />
            </div>
        </AuthenticatedLayout>
    );
}
