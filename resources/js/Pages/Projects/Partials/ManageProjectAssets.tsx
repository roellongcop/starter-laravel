import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import AsyncMultiSelect from '@/Components/AsyncMultiSelect';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { type AdminProject } from '@/types';

/**
 * Set-membership editor for a project's bound assets. Submits the full token
 * list to projects.assets.update, which sync()s the pivot (attach + detach).
 */
export default function ManageProjectAssets({
    project,
    selected,
    onSuccess,
}: {
    project: AdminProject;
    selected: string[];
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
                <AsyncMultiSelect
                    id="assets"
                    className="mt-1"
                    values={data.assets}
                    onChange={(values) => setData('assets', values)}
                    routeName="assets.options"
                    params={{ organization: project.organization || undefined }}
                    placeholder="Select assets"
                    title="Select assets"
                    description="Only assets from this project's organization are shown."
                    emptyText="No assets in this organization."
                    searchPlaceholder="Search assets…"
                />
                <InputError message={errors.assets} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                Save assets
            </Button>
        </form>
    );
}
