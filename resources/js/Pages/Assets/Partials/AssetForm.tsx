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
import { type AdminAsset, type SelectOption } from '@/types';

interface Props {
    asset?: Pick<
        AdminAsset,
        'token' | 'name' | 'id_code' | 'address' | 'organization'
    >;
    organizations: SelectOption[];
    onSuccess?: () => void;
}

export default function AssetForm({ asset, organizations, onSuccess }: Props) {
    const editing = Boolean(asset);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: asset?.name ?? '',
        id_code: asset?.id_code ?? '',
        address: asset?.address ?? '',
        organization:
            asset?.organization ?? String(organizations[0]?.value ?? ''),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (editing && asset) {
            patch(route('assets.update', asset.token), options);
        } else {
            post(route('assets.store'), options);
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
                    placeholder="HQ Building"
                />
                <InputError message={errors.name} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="id_code">ID Code</Label>
                <Input
                    id="id_code"
                    value={data.id_code}
                    onChange={(e) => setData('id_code', e.target.value)}
                    className="mt-1"
                    placeholder="AST-0001"
                />
                <InputError message={errors.id_code} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="address">Address</Label>
                <Textarea
                    id="address"
                    value={data.address}
                    onChange={(e) => setData('address', e.target.value)}
                    className="mt-1"
                    rows={2}
                    placeholder="123 Market Street, Springfield"
                />
                <InputError message={errors.address} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="organization">Organization</Label>
                <Select
                    value={data.organization}
                    onValueChange={(v) => setData('organization', v)}
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

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create asset'}
            </Button>
        </form>
    );
}
