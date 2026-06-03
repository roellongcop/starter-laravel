import { Head, Link } from '@inertiajs/react';

import Can from '@/Components/Can';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminTheme } from '@/types';

function Swatches({ tokens }: { tokens: Record<string, string> }) {
    const entries = Object.entries(tokens);
    if (entries.length === 0) {
        return <p className="text-sm text-muted-foreground">No tokens.</p>;
    }
    return (
        <div className="grid gap-2 sm:grid-cols-2">
            {entries.map(([key, value]) => (
                <div key={key} className="flex items-center gap-2 text-sm">
                    <span
                        className="h-6 w-6 shrink-0 rounded border"
                        style={{ background: `hsl(${value})` }}
                    />
                    <span className="font-mono text-xs">{key}</span>
                    <span className="ml-auto text-xs text-muted-foreground">
                        {value}
                    </span>
                </div>
            ))}
        </div>
    );
}

export default function Show({ theme }: { theme: AdminTheme }) {
    return (
        <AuthenticatedLayout header={theme.name}>
            <Head title={theme.name} />

            <PageHeader
                title={theme.name}
                description={theme.description ?? undefined}
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <Link href={route('themes.index')}>Back</Link>
                        </Button>
                        <Can ability="themes.update">
                            <Button asChild>
                                <Link href={route('themes.edit', theme.id)}>
                                    Edit
                                </Link>
                            </Button>
                        </Can>
                    </>
                }
            />

            {theme.is_default && <Badge className="mb-4">Default theme</Badge>}

            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Light tokens</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Swatches tokens={theme.tokens?.light ?? {}} />
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Dark tokens</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Swatches tokens={theme.tokens?.dark ?? {}} />
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
