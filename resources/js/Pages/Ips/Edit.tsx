import { Head } from '@inertiajs/react';

import BackButton from '@/Components/BackButton.js';
import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminIp, type SelectOption } from '@/types';
import IpForm from './Partials/IpForm';

interface Props {
    ip: AdminIp;
    listTypes: SelectOption[];
}

export default function Edit({ ip, listTypes }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${ip.ip_address}`} />
            <PageHeader
                title={`Edit ${ip.ip_address}`}
                actions={<BackButton fallback={route('ips.index')} />}
            />
            <Card>
                <CardContent className="pt-6">
                    <IpForm ip={ip} listTypes={listTypes} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
