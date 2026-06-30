import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import AsyncMultiSelect from '@/Components/AsyncMultiSelect';
import AsyncSelect from '@/Components/AsyncSelect';
import InputError from '@/Components/InputError';
import OrganizationSelect from '@/Components/OrganizationSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { type AdminTeam } from '@/types';

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
    onSuccess?: () => void;
}

export default function TeamForm({ team, onSuccess }: Props) {
    const editing = Boolean(team);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: team?.name ?? '',
        description: team?.description ?? '',
        organization: team?.organization ?? '',
        team_category: team?.team_category ?? '',
        organization_role: team?.organization_role ?? '',
        members: team?.members ?? ([] as string[]),
    });

    const changeOrganization = (value: string | undefined) => {
        // A category/role belongs to exactly one org, so changing the org always
        // invalidates the current selections — reset them.
        setData((current) => ({
            ...current,
            organization: value ?? '',
            team_category: '',
            organization_role: '',
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
                <OrganizationSelect
                    id="organization"
                    className="mt-1"
                    value={data.organization || undefined}
                    onChange={changeOrganization}
                    invalid={Boolean(errors.organization)}
                />
                <InputError message={errors.organization} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="team_category">Category</Label>
                <AsyncSelect
                    id="team_category"
                    className="mt-1"
                    value={data.team_category || undefined}
                    onChange={(v) => setData('team_category', v ?? '')}
                    routeName="team-categories.options"
                    params={{ organization: data.organization || undefined }}
                    disabled={!data.organization}
                    disabledHint="Select an organization first"
                    placeholder="Select a category"
                    dialogTitle="Select category"
                    searchPlaceholder="Search categories…"
                    emptyText="No categories for this organization."
                    invalid={Boolean(errors.team_category)}
                />
                <InputError message={errors.team_category} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="organization_role">Role</Label>
                <AsyncSelect
                    id="organization_role"
                    className="mt-1"
                    value={data.organization_role || undefined}
                    onChange={(v) => setData('organization_role', v ?? '')}
                    routeName="organization-roles.options"
                    params={{ organization: data.organization || undefined }}
                    disabled={!data.organization}
                    disabledHint="Select an organization first"
                    placeholder="Select a role"
                    dialogTitle="Select role"
                    searchPlaceholder="Search roles…"
                    emptyText="No roles for this organization."
                    invalid={Boolean(errors.organization_role)}
                />
                <InputError
                    message={errors.organization_role}
                    className="mt-1"
                />
            </div>

            <div>
                <Label htmlFor="members">Members</Label>
                <AsyncMultiSelect
                    id="members"
                    className="mt-1"
                    values={data.members}
                    onChange={(values) => setData('members', values)}
                    routeName="users.options"
                    placeholder="Select members"
                    title="Select members"
                    description="Members inherit the team's role within the organization."
                    emptyText="No users found."
                    searchPlaceholder="Search users…"
                />
                <InputError message={errors.members} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create team'}
            </Button>
        </form>
    );
}
