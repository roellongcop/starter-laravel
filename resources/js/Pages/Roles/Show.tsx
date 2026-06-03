import { Head, Link } from '@inertiajs/react';

import Can from '@/Components/Can';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminRole } from '@/types';

export default function Show({ role }: { role: AdminRole }) {
    return (
        <AuthenticatedLayout>
            <Head title={role.name} />

            <PageHeader
                title={role.name}
                description={role.description ?? undefined}
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <Link href={route('roles.index')}>Back</Link>
                        </Button>
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

            <Card>
                <CardHeader>
                    <CardTitle>
                        Permissions ({role.permissions?.length ?? 0})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap gap-2">
                        {role.permissions && role.permissions.length > 0 ? (
                            role.permissions.map((p) => (
                                <Badge key={p} variant="secondary">
                                    {p}
                                </Badge>
                            ))
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No permissions granted.
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
