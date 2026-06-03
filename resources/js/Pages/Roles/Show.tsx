import { Head, Link } from '@inertiajs/react';
import {
    CalendarDays,
    ExternalLink,
    Hash,
    KeyRound,
    Layers,
    ShieldCheck,
} from 'lucide-react';

import BackButton from '@/Components/BackButton';
import Can from '@/Components/Can';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { NavIcon } from '@/lib/navIcons';
import { type AdminRole, type NavItem } from '@/types';

/** Group flat permission names ("users.index") by their resource prefix. */
function groupPermissions(perms: string[]): Record<string, string[]> {
    const groups: Record<string, string[]> = {};
    for (const p of perms) {
        const dot = p.indexOf('.');
        const key = dot === -1 ? '*' : p.slice(0, dot);
        const ability = dot === -1 ? p : p.slice(dot + 1);
        (groups[key] ??= []).push(ability);
    }
    return groups;
}

function Stat({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof KeyRound;
    label: string;
    value: React.ReactNode;
}) {
    return (
        <Card>
            <CardContent className="flex items-center gap-3 p-4">
                <div className="rounded-md bg-muted p-2 text-muted-foreground">
                    <Icon className="h-5 w-5" />
                </div>
                <div>
                    <p className="text-xs uppercase tracking-wide text-muted-foreground">
                        {label}
                    </p>
                    <div className="text-sm font-medium text-foreground">
                        {value}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function MenuRow({ item }: { item: NavItem }) {
    return (
        <div className="flex items-center gap-2 py-1 text-sm">
            <NavIcon
                name={item.icon}
                className="h-4 w-4 shrink-0 text-muted-foreground"
            />
            <span className="text-foreground">{item.label}</span>
            {item.external && (
                <ExternalLink
                    className="h-3 w-3 text-muted-foreground"
                    aria-label="External link"
                />
            )}
        </div>
    );
}

/** One node in the menu tree: a row, plus an indented branch for its children. */
function MenuItem({ item }: { item: NavItem }) {
    return (
        <li className="relative before:absolute before:-left-4 before:top-[14px] before:h-px before:w-3 before:bg-border">
            <MenuRow item={item} />
            {item.children && item.children.length > 0 && (
                <ul className="ml-1.5 border-l border-border pl-4">
                    {item.children.map((child, i) => (
                        <MenuItem key={i} item={child} />
                    ))}
                </ul>
            )}
        </li>
    );
}

export default function Show({ role }: { role: AdminRole }) {
    const groups = groupPermissions(role.permissions ?? []);
    const groupKeys = Object.keys(groups).sort((a, b) =>
        a === '*' ? 1 : b === '*' ? -1 : a.localeCompare(b),
    );
    const isSystem = role.role_type === 'System';

    return (
        <AuthenticatedLayout>
            <Head title={role.name} />

            <PageHeader
                title={role.name}
                description={role.description ?? undefined}
                actions={
                    <>
                        <BackButton fallback={route('roles.index')} />
                        <Can ability="roles.update">
                            <Button asChild>
                                <Link href={route('roles.edit', role.id)}>
                                    Edit
                                </Link>
                            </Button>
                        </Can>
                    </>
                }
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <Stat
                    icon={ShieldCheck}
                    label="Type"
                    value={
                        <Badge variant={isSystem ? 'secondary' : 'outline'}>
                            {role.role_type ?? 'Custom'}
                        </Badge>
                    }
                />
                <Stat
                    icon={KeyRound}
                    label="Permissions"
                    value={role.permissions?.length ?? role.permissions_count}
                />
                <Stat icon={Layers} label="Modules" value={groupKeys.length} />
                <Stat
                    icon={Hash}
                    label="Menu priority"
                    value={role.priority ?? 0}
                />
                <Stat
                    icon={CalendarDays}
                    label="Created"
                    value={
                        role.created_at
                            ? new Date(role.created_at).toLocaleDateString()
                            : '—'
                    }
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Permissions</CardTitle>
                </CardHeader>
                <CardContent>
                    {groupKeys.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No permissions granted.
                        </p>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {groupKeys.map((key) => (
                                <div
                                    key={key}
                                    className="rounded-md border p-3"
                                >
                                    <div className="mb-2 flex items-center justify-between">
                                        <span className="text-sm font-semibold capitalize text-foreground">
                                            {key === '*' ? 'General' : key}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {groups[key].length}
                                        </span>
                                    </div>
                                    <div className="flex flex-wrap gap-1.5">
                                        {groups[key].map((ability) => (
                                            <Badge
                                                key={ability}
                                                variant="secondary"
                                            >
                                                {ability}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Sidebar menu</CardTitle>
                </CardHeader>
                <CardContent>
                    {role.main_navigation && role.main_navigation.length > 0 ? (
                        <ul className="ml-1.5 border-l border-border pl-4">
                            {role.main_navigation.map((node, i) => (
                                <MenuItem key={i} item={node} />
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Auto — the sidebar is generated from this role's
                            permissions. Customize it in the role editor.
                        </p>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
