import { Head, Link } from '@inertiajs/react';
import {
    Archive,
    Bell,
    Circle,
    Download,
    Files,
    Footprints,
    KeyRound,
    ListChecks,
    type LucideIcon,
    Network,
    Palette,
    Upload,
    Users,
} from 'lucide-react';

import PageHeader from '@/Components/PageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type DashboardMetric } from '@/types';

const ICONS: Record<string, LucideIcon> = {
    Users,
    KeyRound,
    Files,
    Network,
    Palette,
    Footprints,
    Archive,
    Download,
    Upload,
    ListChecks,
    Bell,
};

interface Props {
    metrics: DashboardMetric[];
    recent: {
        users: { id: number; name: string; email: string }[];
    };
}

export default function Dashboard({ metrics, recent }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <PageHeader
                title="Dashboard"
                description="Overview of your admin resources."
            />

            <div className="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                {metrics.map((m) => {
                    const Icon = ICONS[m.icon] ?? Circle;
                    return (
                        <Link key={m.label} href={m.href}>
                            <Card className="transition-colors hover:bg-accent">
                                <CardContent className="flex items-center justify-between p-6">
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            {m.label}
                                        </p>
                                        <p className="text-3xl font-semibold">
                                            {m.count}
                                        </p>
                                    </div>
                                    <Icon className="h-8 w-8 text-muted-foreground/60" />
                                </CardContent>
                            </Card>
                        </Link>
                    );
                })}
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Recent users</CardTitle>
                </CardHeader>
                <CardContent>
                    {recent.users.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No users yet.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {recent.users.map((u) => (
                                <li
                                    key={u.id}
                                    className="flex items-center justify-between py-2 text-sm"
                                >
                                    <span className="font-medium">
                                        {u.name}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {u.email}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
