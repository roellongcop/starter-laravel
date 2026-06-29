import { Head } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    type AdminAsset,
    type AdminMilestone,
    type Crumb,
    type DataTagOption,
    type SelectOption,
} from '@/types';
import MilestoneBoard from './Partials/MilestoneBoard';

interface Props {
    project: { token: string; name: string };
    asset: AdminAsset;
    milestones: AdminMilestone[];
    canManage: boolean;
    userOptions: SelectOption[];
    referenceFileOptions: SelectOption[];
    dataTags: DataTagOption[];
}

export default function AssetBoard({
    project,
    asset,
    milestones,
    canManage,
    userOptions,
    referenceFileOptions,
    dataTags,
}: Props) {
    const breadcrumbs: Crumb[] = [
        { label: 'Projects', href: route('projects.index') },
        { label: project.name, href: route('projects.show', project.token) },
        { label: asset.name },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`${asset.name} · ${project.name}`} />

            <PageHeader title={asset.name} breadcrumbs={breadcrumbs} />

            <MilestoneBoard
                projectToken={project.token}
                assetToken={asset.token}
                assetOrganization={asset.organization}
                milestones={milestones}
                canManage={canManage}
                userOptions={userOptions}
                referenceFileOptions={referenceFileOptions}
                dataTags={dataTags}
            />
        </AuthenticatedLayout>
    );
}
