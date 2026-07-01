import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import PageHeader from '@/Components/PageHeader';
import TagEditor from '@/Components/TagEditor';
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
import { usePermissions } from '@/lib/permissions';
import { type AdminAsset, type Crumb } from '@/types';
import AssetForm from './Partials/AssetForm';

interface Props {
    asset: AdminAsset;
}

export default function Show({ asset }: Props) {
    const { can } = usePermissions();
    const [editOpen, setEditOpen] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    const destroy = () =>
        router.delete(route('assets.destroy', asset.token), {
            onFinish: () => setConfirmingDelete(false),
        });

    const breadcrumbs: Crumb[] = [
        { label: 'Assets', href: route('assets.index') },
        { label: asset.name },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={asset.name} />

            <PageHeader
                title={asset.name}
                breadcrumbs={breadcrumbs}
                actions={
                    <>
                        <Can ability="assets.update">
                            <Button onClick={() => setEditOpen(true)}>
                                Edit
                            </Button>
                        </Can>
                        <Can ability="assets.delete">
                            <Button
                                variant="destructive"
                                onClick={() => setConfirmingDelete(true)}
                            >
                                Delete
                            </Button>
                        </Can>
                    </>
                }
            />

            <Card>
                <CardContent className="space-y-4 pt-6">
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Organization
                        </span>
                        <p className="mt-1 text-sm">
                            {asset.organization_name || '—'}
                        </p>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            ID Code
                        </span>
                        <p className="mt-1 font-mono text-sm">
                            {asset.id_code || '—'}
                        </p>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Address
                        </span>
                        <p className="mt-1 text-sm">{asset.address || '—'}</p>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Tags
                        </span>
                        <TagEditor
                            className="mt-1"
                            tags={asset.tags}
                            organization={asset.organization}
                            type="assets"
                            token={asset.token}
                            canEdit={can('assets.update')}
                        />
                    </div>
                </CardContent>
            </Card>

            <Sheet open={editOpen} onOpenChange={setEditOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>{`Edit ${asset.name}`}</SheetTitle>
                        <SheetDescription>
                            An asset belonging to an organization.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <AssetForm
                            asset={asset}
                            onSuccess={() => setEditOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <ConfirmDialog
                open={confirmingDelete}
                onOpenChange={setConfirmingDelete}
                title={`Delete ${asset.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
