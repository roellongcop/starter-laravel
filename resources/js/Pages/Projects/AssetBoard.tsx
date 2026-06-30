import { Head } from '@inertiajs/react';
import axios from 'axios';

import Can from '@/Components/Can';
import PageHeader from '@/Components/PageHeader';
import StatusBadge from '@/Components/StatusBadge';
import StatusDropdown from '@/Components/StatusDropdown';
import TagBadges from '@/Components/TagBadges';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    type AdminMilestone,
    type Crumb,
    type ProjectAsset,
    type SelectOption,
} from '@/types';
import MilestoneBoard from './Partials/MilestoneBoard';

interface Props {
    project: { token: string; name: string };
    asset: ProjectAsset;
    milestones: AdminMilestone[];
    canManage: boolean;
    statusOptions: SelectOption[];
}

export default function AssetBoard({
    project,
    asset,
    milestones,
    canManage,
    statusOptions,
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

            <div className="space-y-6">
                <Card>
                    <CardContent className="p-4">
                        <dl className="grid grid-cols-[7rem_1fr] items-center gap-x-4 gap-y-2.5 text-sm">
                            <dt className="text-muted-foreground">
                                Organization
                            </dt>
                            <dd className="truncate">
                                {asset.organization_name || '—'}
                            </dd>

                            <dt className="text-muted-foreground">ID Code</dt>
                            <dd className="font-mono">
                                {asset.id_code || '—'}
                            </dd>

                            <dt className="self-start text-muted-foreground">
                                Address
                            </dt>
                            <dd className="whitespace-pre-line">
                                {asset.address || '—'}
                            </dd>

                            <dt className="text-muted-foreground">Status</dt>
                            <dd>
                                <Can
                                    ability="projects.update"
                                    fallback={
                                        <StatusBadge status={asset.status} />
                                    }
                                >
                                    <StatusDropdown
                                        value={asset.status}
                                        options={statusOptions}
                                        onSelect={(status) =>
                                            axios.patch(
                                                route(
                                                    'projects.assets.status',
                                                    [
                                                        project.token,
                                                        asset.token,
                                                    ],
                                                ),
                                                { status },
                                            )
                                        }
                                    />
                                </Can>
                            </dd>

                            <dt className="self-start text-muted-foreground">
                                Tags
                            </dt>
                            <dd>
                                {asset.tags.length > 0 ? (
                                    <TagBadges tags={asset.tags} />
                                ) : (
                                    <span className="text-muted-foreground">
                                        —
                                    </span>
                                )}
                            </dd>
                        </dl>
                    </CardContent>
                </Card>

                <MilestoneBoard
                    projectToken={project.token}
                    assetToken={asset.token}
                    assetOrganization={asset.organization}
                    milestones={milestones}
                    canManage={canManage}
                />
            </div>
        </AuthenticatedLayout>
    );
}
