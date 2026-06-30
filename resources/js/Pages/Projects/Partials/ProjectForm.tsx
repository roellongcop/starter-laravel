import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import AsyncMultiSelect from '@/Components/AsyncMultiSelect';
import InputError from '@/Components/InputError';
import OrganizationSelect from '@/Components/OrganizationSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { type AdminProject } from '@/types';

interface Props {
    project?: Pick<
        AdminProject,
        'token' | 'name' | 'description' | 'private' | 'organization' | 'tags'
    >;
    onSuccess?: () => void;
}

export default function ProjectForm({ project, onSuccess }: Props) {
    const editing = Boolean(project);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: project?.name ?? '',
        description: project?.description ?? '',
        private: project?.private ?? false,
        organization: project?.organization ?? '',
        tags: project?.tags?.map((t) => t.token) ?? [],
    });

    const changeOrganization = (value: string | undefined) => {
        // Tags belong to one organization, so an org change clears them.
        setData((current) => ({
            ...current,
            organization: value ?? '',
            tags: [],
        }));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (editing && project) {
            patch(route('projects.update', project.token), options);
        } else {
            post(route('projects.store'), options);
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
                    placeholder="Website Redesign"
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

            <div className="flex items-center justify-between rounded-lg border p-3">
                <div>
                    <Label htmlFor="private">Private</Label>
                    <p className="text-sm text-muted-foreground">
                        Hide this project from broader visibility.
                    </p>
                </div>
                <Switch
                    id="private"
                    checked={data.private}
                    onCheckedChange={(v) => setData('private', v)}
                />
            </div>
            <InputError message={errors.private} className="mt-1" />

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create project'}
            </Button>
        </form>
    );
}
