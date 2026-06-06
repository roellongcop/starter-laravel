import { Deferred, Head, Link } from '@inertiajs/react';
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
    metrics?: DashboardMetric[];
    recent: {
        users: { token: string; name: string; email: string }[];
    };
}

const METRICS_GRID =
    'mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4';

function MetricsSkeleton() {
    return (
        <div className={METRICS_GRID}>
            {Array.from({ length: 8 }).map((_, i) => (
                <Card key={i} className="animate-pulse">
                    <CardContent className="flex items-center justify-between p-6">
                        <div className="space-y-2">
                            <div className="h-4 w-20 rounded bg-muted" />
                            <div className="h-8 w-12 rounded bg-muted" />
                        </div>
                        <div className="h-8 w-8 rounded-full bg-muted" />
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

export default function Dashboard({ metrics, recent }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <PageHeader
                title="Dashboard"
                description="Overview of your admin resources."
            />

            <Deferred data="metrics" fallback={<MetricsSkeleton />}>
                <div className={METRICS_GRID}>
                    {(metrics ?? []).map((m) => {
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
            </Deferred>

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
                                    key={u.token}
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
