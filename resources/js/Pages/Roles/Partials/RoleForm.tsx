import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { type AdminRole } from '@/types';

interface Props {
    role?: AdminRole;
    /** { resourceKey: ["users.index", ...], "*": ["view-inactive"] } */
    permissionGroups: Record<string, string[]>;
}

export default function RoleForm({ role, permissionGroups }: Props) {
    const editing = Boolean(role);

    const { data, setData, post, patch, processing, errors } = useForm<{
        name: string;
        description: string;
        permissions: string[];
    }>({
        name: role?.name ?? '',
        description: role?.description ?? '',
        permissions: role?.permissions ?? [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (editing && role) {
            patch(route('roles.update', role.id));
        } else {
            post(route('roles.store'));
        }
    };

    const toggle = (name: string) =>
        setData(
            'permissions',
            data.permissions.includes(name)
                ? data.permissions.filter((p) => p !== name)
                : [...data.permissions, name],
        );

    const toggleGroup = (names: string[], all: boolean) =>
        setData(
            'permissions',
            all
                ? data.permissions.filter((p) => !names.includes(p))
                : Array.from(new Set([...data.permissions, ...names])),
        );

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid max-w-2xl gap-4 sm:grid-cols-2">
                <div>
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        className="mt-1"
                        disabled={role?.role_type === 'System'}
                    />
                    <InputError message={errors.name} className="mt-1" />
                    {role?.role_type === 'System' && (
                        <p className="mt-1 text-xs text-muted-foreground">
                            System roles cannot be renamed.
                        </p>
                    )}
                </div>
                <div>
                    <Label htmlFor="description">Description</Label>
                    <Input
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        className="mt-1"
                    />
                    <InputError message={errors.description} className="mt-1" />
                </div>
            </div>

            <div>
                <Label>Permissions</Label>
                <div className="mt-2 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {Object.entries(permissionGroups).map(([key, names]) => {
                        const all = names.every((n) =>
                            data.permissions.includes(n),
                        );
                        return (
                            <div key={key} className="rounded-md border p-3">
                                <button
                                    type="button"
                                    onClick={() => toggleGroup(names, all)}
                                    className="mb-2 text-sm font-semibold capitalize text-foreground hover:underline"
                                >
                                    {key === '*' ? 'General' : key}
                                </button>
                                <div className="space-y-1.5">
                                    {names.map((name) => (
                                        <label
                                            key={name}
                                            className="flex items-center gap-2 text-sm text-muted-foreground"
                                        >
                                            <Checkbox
                                                checked={data.permissions.includes(
                                                    name,
                                                )}
                                                onCheckedChange={() =>
                                                    toggle(name)
                                                }
                                            />
                                            {name.includes('.')
                                                ? name.split('.')[1]
                                                : name}
                                        </label>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
                <InputError message={errors.permissions} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create role'}
            </Button>
        </form>
    );
}
