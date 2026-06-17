import { Head } from '@inertiajs/react';
import { useState } from 'react';

import Can from '@/Components/Can';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminIp, type SelectOption } from '@/types';
import IpForm from './Partials/IpForm';

interface Props {
    ip: AdminIp;
    listTypes: SelectOption[];
}

export default function Show({ ip, listTypes }: Props) {
    const [editOpen, setEditOpen] = useState(false);

    return (
        <AuthenticatedLayout>
            <Head title={ip.ip_address} />

            <PageHeader
                title={ip.ip_address}
                breadcrumbs={[
                    { label: 'IP Lists', href: route('ips.index') },
                    { label: ip.ip_address },
                ]}
                actions={
                    <>
                        <Can ability="ips.update">
                            <Button onClick={() => setEditOpen(true)}>
                                Edit
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

            <Sheet open={editOpen} onOpenChange={setEditOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>{`Edit ${ip.ip_address}`}</SheetTitle>
                        <SheetDescription>
                            Whitelist or blacklist entry enforced by the IP
                            middleware.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <IpForm
                            ip={ip}
                            listTypes={listTypes}
                            onSuccess={() => setEditOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>
        </AuthenticatedLayout>
    );
}
