import { Head } from '@inertiajs/react';

import BackButton from '@/Components/BackButton';
import PageHeader from '@/Components/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type SelectOption } from '@/types';
import IpForm from './Partials/IpForm';

export default function Create({ listTypes }: { listTypes: SelectOption[] }) {
    return (
        <AuthenticatedLayout>
            <Head title="New IP Entry" />
            <PageHeader
                title="New IP Entry"
                actions={<BackButton fallback={route('ips.index')} />}
            />
            <Card>
                <CardContent className="pt-6">
                    <IpForm listTypes={listTypes} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
