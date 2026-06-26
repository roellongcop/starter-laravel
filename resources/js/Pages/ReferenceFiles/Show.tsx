import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import PageHeader from '@/Components/PageHeader';
import TagBadges from '@/Components/TagBadges';
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
import {
    type AdminReferenceFile,
    type Crumb,
    type DataTagOption,
    type SelectOption,
} from '@/types';
import ReferenceFileForm from './Partials/ReferenceFileForm';

interface Props {
    reference: AdminReferenceFile;
    organizations: SelectOption[];
    dataTags: DataTagOption[];
}

export default function Show({ reference, organizations, dataTags }: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    const destroy = () =>
        router.delete(route('reference-files.destroy', reference.token), {
            onFinish: () => setConfirmingDelete(false),
        });

    const breadcrumbs: Crumb[] = [
        { label: 'Reference Files', href: route('reference-files.index') },
        { label: reference.name },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={reference.name} />

            <PageHeader
                title={reference.name}
                breadcrumbs={breadcrumbs}
                actions={
                    <>
                        <Can ability="reference-files.update">
                            <Button onClick={() => setEditOpen(true)}>
                                Edit
                            </Button>
                        </Can>
                        <Can ability="reference-files.delete">
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
                            {reference.organization_name || '—'}
                        </p>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Description
                        </span>
                        <p className="mt-1 text-sm">
                            {reference.description || '—'}
                        </p>
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            File
                        </span>
                        {reference.file_url ? (
                            <p className="mt-1">
                                <a
                                    href={reference.file_url}
                                    className="inline-flex items-center gap-1.5 text-sm text-primary hover:underline"
                                >
                                    <Download className="h-4 w-4" />
                                    {reference.file_name ?? 'Download'}
                                </a>
                            </p>
                        ) : (
                            <p className="mt-1 text-sm text-muted-foreground">
                                No file attached.
                            </p>
                        )}
                    </div>
                    <div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            Tags
                        </span>
                        {reference.tags.length > 0 ? (
                            <TagBadges tags={reference.tags} className="mt-1" />
                        ) : (
                            <p className="mt-1 text-sm text-muted-foreground">
                                No tags.
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>

            <Sheet open={editOpen} onOpenChange={setEditOpen}>
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>{`Edit ${reference.name}`}</SheetTitle>
                        <SheetDescription>
                            A reference with an optional attached file.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6">
                        <ReferenceFileForm
                            reference={reference}
                            organizations={organizations}
                            dataTags={dataTags}
                            onSuccess={() => setEditOpen(false)}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <ConfirmDialog
                open={confirmingDelete}
                onOpenChange={setConfirmingDelete}
                title={`Delete ${reference.name}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
