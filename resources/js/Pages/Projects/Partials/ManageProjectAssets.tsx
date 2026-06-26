import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import MultiSelect from '@/Components/MultiSelect';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { type AdminProject, type SelectOption } from '@/types';

/**
 * Set-membership editor for a project's bound assets. Submits the full token
 * list to projects.assets.update, which sync()s the pivot (attach + detach).
 */
export default function ManageProjectAssets({
    project,
    selected,
    assetOptions,
    onSuccess,
}: {
    project: AdminProject;
    selected: string[];
    assetOptions: SelectOption[];
    onSuccess: () => void;
}) {
    const { data, setData, put, processing, errors } = useForm({
        assets: selected,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('projects.assets.update', project.token), {
            preserveScroll: true,
            onSuccess,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div>
                <Label htmlFor="assets">Assets</Label>
                <MultiSelect
                    id="assets"
                    className="mt-1"
                    options={assetOptions}
                    selected={data.assets}
                    onChange={(values) => setData('assets', values)}
                    placeholder="Select assets"
                    title="Select assets"
                    description="Only assets from this project's organization are shown."
                    emptyText="No assets in this organization."
                />
                <InputError message={errors.assets} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                Save assets
            </Button>
        </form>
    );
}
