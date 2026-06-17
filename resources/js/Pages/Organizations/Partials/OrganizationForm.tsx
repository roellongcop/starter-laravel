import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
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
import { type AdminOrganization, type SelectOption } from '@/types';

interface Props {
    organization?: AdminOrganization;
    users: SelectOption[];
    onSuccess?: () => void;
}

const NONE = 'none';

export default function OrganizationForm({
    organization,
    users,
    onSuccess,
}: Props) {
    const editing = Boolean(organization);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: organization?.name ?? '',
        description: organization?.description ?? '',
        point_of_contact: organization?.point_of_contact ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (editing && organization) {
            patch(route('organizations.update', organization.token), options);
        } else {
            post(route('organizations.store'), options);
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
                    placeholder="Acme Corporation"
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
                    rows={3}
                />
                <InputError message={errors.description} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="point_of_contact">Point of contact</Label>
                <Select
                    value={data.point_of_contact || NONE}
                    onValueChange={(v) =>
                        setData('point_of_contact', v === NONE ? '' : v)
                    }
                >
                    <SelectTrigger id="point_of_contact" className="mt-1">
                        <SelectValue placeholder="— None —" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={NONE}>— None —</SelectItem>
                        {users.map((o) => (
                            <SelectItem key={o.value} value={String(o.value)}>
                                {o.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError
                    message={errors.point_of_contact}
                    className="mt-1"
                />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create organization'}
            </Button>
        </form>
    );
}
