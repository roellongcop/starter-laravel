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
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import {
    type AdminProject,
    type DataTagOption,
    type SelectOption,
} from '@/types';

interface Props {
    project?: Pick<
        AdminProject,
        'token' | 'name' | 'description' | 'private' | 'organization' | 'tags'
    >;
    organizations: SelectOption[];
    dataTags: DataTagOption[];
    onSuccess?: () => void;
}

export default function ProjectForm({
    project,
    organizations,
    dataTags,
    onSuccess,
}: Props) {
    const editing = Boolean(project);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: project?.name ?? '',
        description: project?.description ?? '',
        private: project?.private ?? false,
        organization:
            project?.organization ?? String(organizations[0]?.value ?? ''),
        tags: project?.tags?.map((t) => t.token) ?? [],
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
