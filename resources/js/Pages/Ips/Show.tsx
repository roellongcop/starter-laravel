import { Head, Link } from '@inertiajs/react';

import BackButton from '@/Components/BackButton.js';
import Can from '@/Components/Can';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminIp } from '@/types';

export default function Show({ ip }: { ip: AdminIp }) {
    return (
        <AuthenticatedLayout>
            <Head title={ip.ip_address} />

            <PageHeader
                title={ip.ip_address}
                actions={
                    <>
                        <BackButton fallback={route('ips.index')} />
                        <Can ability="ips.update">
                            <Button asChild>
                                <Link href={route('ips.edit', ip.token)}>
                                    Edit
                                </Link>
                            </Button>
                        </Can>
                    </>
                }
            />

            <Card>
                <CardContent className="space-y-4 pt-6">
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            List type
                        </span>
                        <div className="mt-1">
                            <Badge
                                variant={
                                    ip.list_type === 'Whitelist'
                                        ? 'default'
                                        : 'destructive'
                                }
                            >
                                {ip.list_type}
                            </Badge>
                        </div>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Description
                        </span>
                        <p className="mt-1 text-sm">{ip.description || '—'}</p>
                    </div>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
