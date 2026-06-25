import { useForm } from '@inertiajs/react';
import { FormEventHandler, useMemo } from 'react';

import InputError from '@/Components/InputError';
import MultiSelect from '@/Components/MultiSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import {
    type AdminTeam,
    type OrgScopedOption,
    type SelectOption,
} from '@/types';

interface Props {
    team?: Pick<
        AdminTeam,
        | 'token'
        | 'name'
        | 'description'
        | 'organization'
        | 'team_category'
        | 'organization_role'
        | 'members'
    >;
    organizations: SelectOption[];
    categories: OrgScopedOption[];
    organizationRoles: OrgScopedOption[];
    users: SelectOption[];
    onSuccess?: () => void;
}

export default function TeamForm({
    team,
    organizations,
    categories,
    organizationRoles,
    users,
    onSuccess,
}: Props) {
    const editing = Boolean(team);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: team?.name ?? '',
        description: team?.description ?? '',
        organization:
            team?.organization ?? String(organizations[0]?.value ?? ''),
        team_category: team?.team_category ?? '',
        organization_role: team?.organization_role ?? '',
        members: team?.members ?? ([] as string[]),
    });

    // Categories and roles are per-organization: only show those belonging to
    // the chosen org.
    const availableCategories = useMemo(
        () => categories.filter((c) => c.organization === data.organization),
        [categories, data.organization],
    );
    const availableRoles = useMemo(
        () =>
            organizationRoles.filter(
                (r) => r.organization === data.organization,
            ),
        [organizationRoles, data.organization],
    );

    const changeOrganization = (value: string) => {
        // Drop the category/role when they no longer belong to the chosen org.
        const categoryValid = categories.some(
            (c) => c.organization === value && c.value === data.team_category,
        );
        const roleValid = organizationRoles.some(
            (r) =>
                r.organization === value && r.value === data.organization_role,
        );
        setData((current) => ({
            ...current,
            organization: value,
            team_category: categoryValid ? current.team_category : '',
            organization_role: roleValid ? current.organization_role : '',
        }));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (editing && team) {
            patch(route('teams.update', team.token), options);
        } else {
            post(route('teams.store'), options);
        }
    };

    return (
        <form onSubmit={submit} className="max-w-xl space-y-4">
            <div>
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="mt-1"
                    placeholder="Core Team"
                />
                <InputError message={errors.name} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="description">Description</Label>
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    className="mt-1"
                    rows={2}
                    placeholder="Optional description"
                />
                <InputError message={errors.description} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="organization">Organization</Label>
                <Select
                    value={data.organization}
                    onValueChange={changeOrganization}
                >
                    <SelectTrigger id="organization" className="mt-1">
                        <SelectValue placeholder="Select an organization" />
                    </SelectTrigger>
                    <SelectContent>
                        {organizations.map((o) => (
                            <SelectItem key={o.value} value={String(o.value)}>
                                {o.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.organization} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="team_category">Category</Label>
                <Select
                    value={data.team_category}
                    onValueChange={(v) => setData('team_category', v)}
                    disabled={availableCategories.length === 0}
                >
                    <SelectTrigger id="team_category" className="mt-1">
                        <SelectValue
                            placeholder={
                                availableCategories.length === 0
                                    ? 'No categories for this organization'
                                    : 'Select a category'
                            }
                        />
                    </SelectTrigger>
                    <SelectContent>
                        {availableCategories.map((c) => (
                            <SelectItem key={c.value} value={String(c.value)}>
                                {c.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.team_category} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="organization_role">Role</Label>
                <Select
                    value={data.organization_role}
                    onValueChange={(v) => setData('organization_role', v)}
                    disabled={availableRoles.length === 0}
                >
                    <SelectTrigger id="organization_role" className="mt-1">
                        <SelectValue
                            placeholder={
                                availableRoles.length === 0
                                    ? 'No roles for this organization'
                                    : 'Select a role'
                            }
                        />
                    </SelectTrigger>
                    <SelectContent>
                        {availableRoles.map((r) => (
                            <SelectItem key={r.value} value={String(r.value)}>
                                {r.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError
                    message={errors.organization_role}
                    className="mt-1"
                />
            </div>

            <div>
                <Label htmlFor="members">Members</Label>
                <MultiSelect
                    id="members"
                    className="mt-1"
                    options={users}
                    selected={data.members}
                    onChange={(values) => setData('members', values)}
                    placeholder="Select members"
                    title="Select members"
                    description="Members inherit the team's role within the organization."
                    emptyText="No users found."
                />
                <InputError message={errors.members} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create team'}
            </Button>
        </form>
    );
}
