import { useForm } from '@inertiajs/react';
import { FormEventHandler, useMemo } from 'react';

import InputError from '@/Components/InputError';
import MenuBuilder from '@/Components/MenuBuilder';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { type AdminRole, type MenuCatalogItem, type NavItem } from '@/types';

interface Props {
    role?: AdminRole;
    /** { resourceKey: ["users.index", ...], "*": ["view-inactive"] } */
    permissionGroups: Record<string, string[]>;
    menuCatalog: MenuCatalogItem[];
}

export default function RoleForm({
    role,
    permissionGroups,
    menuCatalog,
}: Props) {
    const editing = Boolean(role);

    const { data, setData, post, patch, processing, errors } = useForm<{
        name: string;
        description: string;
        permissions: string[];
        main_navigation: NavItem[];
        priority: number;
    }>({
        name: role?.name ?? '',
        description: role?.description ?? '',
        permissions: role?.permissions ?? [],
        main_navigation: role?.main_navigation ?? [],
        priority: role?.priority ?? 0,
    });

    // Module keys the role can access (any selected ability) — the builder only
    // offers these as addable modules.
    const accessibleKeys = useMemo(() => {
        const keys = new Set<string>();
        for (const p of data.permissions) {
            const i = p.indexOf('.');
            if (i > 0) keys.add(p.slice(0, i));
        }
        return [...keys];
    }, [data.permissions]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (editing && role) {
            patch(route('roles.update', role.token));
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

    const allNames = Object.values(permissionGroups).flat();
    const allChecked =
        allNames.length > 0 &&
        allNames.every((n) => data.permissions.includes(n));

    const toggleAll = (all: boolean) =>
        setData(
            'permissions',
            all
                ? data.permissions.filter((p) => !allNames.includes(p))
                : Array.from(new Set([...data.permissions, ...allNames])),
        );

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid max-w-2xl gap-4 sm:grid-cols-2">
                <div>
                    <Label htmlFor="name" required>
                        Name
                    </Label>
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
                <div className="flex items-center justify-between">
                    <Label>Permissions</Label>
                    <label className="flex items-center gap-2 text-sm font-medium">
                        <Checkbox
                            checked={allChecked}
                            onCheckedChange={() => toggleAll(allChecked)}
                        />
                        Select all
                    </label>
                </div>
                <div className="mt-2 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {Object.entries(permissionGroups).map(([key, names]) => {
                        const all = names.every((n) =>
                            data.permissions.includes(n),
                        );
                        return (
                            <div key={key} className="rounded-md border p-3">
                                <label className="mb-2 flex items-center gap-2 text-sm font-semibold capitalize text-foreground">
                                    <Checkbox
                                        checked={all}
                                        onCheckedChange={() =>
                                            toggleGroup(names, all)
                                        }
                                    />
                                    {key === '*' ? 'General' : key}
                                </label>
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

            <div>
                <div className="flex items-center justify-between">
                    <Label>Sidebar menu</Label>
                    <div className="flex items-center gap-2">
                        <Label
                            htmlFor="priority"
                            className="text-xs text-muted-foreground"
                        >
                            Priority
                        </Label>
                        <Input
                            id="priority"
                            type="number"
                            min={0}
                            value={data.priority}
                            onChange={(e) =>
                                setData('priority', Number(e.target.value))
                            }
                            className="h-8 w-20"
                        />
                    </div>
                </div>
                <p className="mb-2 mt-1 text-xs text-muted-foreground">
                    Leave empty to auto-build the menu from the permissions
                    above. Customize to control order, grouping, labels and
                    external links. Higher priority wins when a user has
                    multiple roles.
                </p>
                <MenuBuilder
                    value={data.main_navigation}
                    onChange={(next) => setData('main_navigation', next)}
                    catalog={menuCatalog}
                    accessibleKeys={accessibleKeys}
                />
                <InputError message={errors.main_navigation} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create role'}
            </Button>
        </form>
    );
}
