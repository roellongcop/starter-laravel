import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import AsyncMultiSelect from '@/Components/AsyncMultiSelect';
import InputError from '@/Components/InputError';
import OrganizationSelect from '@/Components/OrganizationSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { type AdminAsset } from '@/types';

interface Props {
    asset?: Pick<
        AdminAsset,
        'token' | 'name' | 'id_code' | 'address' | 'organization' | 'tags'
    >;
    onSuccess?: () => void;
}

export default function AssetForm({ asset, onSuccess }: Props) {
    const editing = Boolean(asset);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: asset?.name ?? '',
        id_code: asset?.id_code ?? '',
        address: asset?.address ?? '',
        organization: asset?.organization ?? '',
        tags: asset?.tags?.map((t) => t.token) ?? [],
    });

    const changeOrganization = (value: string | undefined) => {
        // A tag belongs to exactly one org, so changing org invalidates the
        // current selection — reset tags whenever the organization changes.
        setData((current) => ({
            ...current,
            organization: value ?? '',
            tags: [],
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
                <Label htmlFor="name" required>
                    Name
                </Label>
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
                <Label htmlFor="id_code" required>
                    ID Code
                </Label>
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
                <Label htmlFor="address" required>
                    Address
                </Label>
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
                <Label htmlFor="organization" required>
                    Organization
                </Label>
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
                <Label htmlFor="tags">Tags</Label>
                <AsyncMultiSelect
                    id="tags"
                    className="mt-1"
                    values={data.tags}
                    onChange={(values) => setData('tags', values)}
                    routeName="data-tags.options"
                    params={{ organization: data.organization || undefined }}
                    disabled={!data.organization}
                    disabledHint="Select an organization first"
                    placeholder="Select tags"
                    title="Select tags"
                    description="Only tags from the chosen organization are shown."
                    emptyText="No tags for this organization."
                    searchPlaceholder="Search tags…"
                />
                <InputError message={errors.tags} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create asset'}
            </Button>
        </form>
    );
}
