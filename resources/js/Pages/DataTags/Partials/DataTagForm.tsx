import { useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import OrganizationSelect from '@/Components/OrganizationSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { cn } from '@/lib/utils';
import { type AdminDataTag } from '@/types';

interface Props {
    dataTag?: Pick<
        AdminDataTag,
        'token' | 'name' | 'description' | 'color' | 'organization'
    >;
    colors: string[];
    onSuccess?: () => void;
}

export default function DataTagForm({ dataTag, colors, onSuccess }: Props) {
    const editing = Boolean(dataTag);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: dataTag?.name ?? '',
        description: dataTag?.description ?? '',
        organization: dataTag?.organization ?? '',
        color: dataTag?.color ?? colors[0] ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (editing && dataTag) {
            patch(route('data-tags.update', dataTag.token), options);
        } else {
            post(route('data-tags.store'), options);
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
                    placeholder="Priority"
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

            <div>
                <Label>Color</Label>
                <div className="mt-2 flex flex-wrap gap-2">
                    {colors.map((color) => {
                        const selected = data.color === color;
                        return (
                            <button
                                key={color}
                                type="button"
                                onClick={() => setData('color', color)}
                                aria-label={`Select color ${color}`}
                                aria-pressed={selected}
                                className={cn(
                                    'flex h-7 w-7 items-center justify-center rounded-full text-white ring-offset-2 ring-offset-background transition',
                                    selected
                                        ? 'ring-2 ring-foreground'
                                        : 'hover:scale-110',
                                )}
                                style={{ backgroundColor: color }}
                            >
                                {selected && <Check className="h-4 w-4" />}
                            </button>
                        );
                    })}
                </div>
                <InputError message={errors.color} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create tag'}
            </Button>
        </form>
    );
}
