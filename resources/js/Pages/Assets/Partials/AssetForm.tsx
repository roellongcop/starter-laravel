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
    type AdminAsset,
    type DataTagOption,
    type SelectOption,
} from '@/types';

interface Props {
    asset?: Pick<
        AdminAsset,
        'token' | 'name' | 'id_code' | 'address' | 'organization' | 'tags'
    >;
    organizations: SelectOption[];
    dataTags: DataTagOption[];
    onSuccess?: () => void;
}

export default function AssetForm({
    asset,
    organizations,
    dataTags,
    onSuccess,
}: Props) {
    const editing = Boolean(asset);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: asset?.name ?? '',
        id_code: asset?.id_code ?? '',
        address: asset?.address ?? '',
        organization:
            asset?.organization ?? String(organizations[0]?.value ?? ''),
        tags: asset?.tags?.map((t) => t.token) ?? [],
    });

    // Tags are per-organization: only offer those belonging to the chosen org.
    const availableTags = useMemo(
        () => dataTags.filter((t) => t.organization === data.organization),
        [dataTags, data.organization],
    );

    const changeOrganization = (value: string) => {
        // Drop tags that no longer belong to the chosen organization.
        const validTags = data.tags.filter((token) =>
            dataTags.some((t) => t.organization === value && t.value === token),
        );
        setData((current) => ({
            ...current,
            organization: value,
            tags: validTags,
        }));
    };

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
                <Label htmlFor="tags">Tags</Label>
                <MultiSelect
                    id="tags"
                    className="mt-1"
                    options={availableTags}
                    selected={data.tags}
                    onChange={(values) => setData('tags', values)}
                    placeholder="Select tags"
                    title="Select tags"
                    description="Only tags from the chosen organization are shown."
                    emptyText="No tags for this organization."
                />
                <InputError message={errors.tags} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create asset'}
            </Button>
        </form>
    );
}
