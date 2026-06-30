import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import OrganizationSelect from '@/Components/OrganizationSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { type AdminOrganizationRole } from '@/types';

interface Props {
    role?: Pick<
        AdminOrganizationRole,
        'token' | 'name' | 'description' | 'organization'
    >;
    onSuccess?: () => void;
}

export default function OrganizationRoleForm({ role, onSuccess }: Props) {
    const editing = Boolean(role);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: role?.name ?? '',
        description: role?.description ?? '',
        organization: role?.organization ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (editing && role) {
            patch(route('organization-roles.update', role.token), options);
        } else {
            post(route('organization-roles.store'), options);
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
                    placeholder="Project Manager"
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
                    onChange={(v) => setData('organization', v ?? '')}
                    invalid={Boolean(errors.organization)}
                />
                <InputError message={errors.organization} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create role'}
            </Button>
        </form>
    );
}
