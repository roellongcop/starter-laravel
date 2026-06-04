import { router } from '@inertiajs/react';
import { useState } from 'react';

import ConfirmDialog from '@/Components/ConfirmDialog';
import DocumentList from '@/Components/DocumentList';
import FileDropzone from '@/Components/FileDropzone';
import { type AdminDocument, type CursorResponse } from '@/types';

export default function UpdateDocumentsForm({
    documents,
    className = '',
}: {
    documents: CursorResponse<AdminDocument>;
    className?: string;
}) {
    const [deleting, setDeleting] = useState<AdminDocument | null>(null);

    const refresh = () => router.reload({ only: ['documents'] });

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('documents.destroy', deleting.token), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                    My Documents
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Upload PDF or Word documents (.pdf, .doc, .docx) to your
                    account.
                </p>
            </header>

            <div className="mt-6 space-y-4">
                <FileDropzone
                    uploadUrl={route('documents.store')}
                    hint="PDF, DOC or DOCX — up to 10 MB each"
                    onUploaded={refresh}
                />

                <DocumentList documents={documents} onDelete={setDeleting} />
            </div>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
                title={`Delete ${deleting?.name}?`}
                description="This permanently removes the document."
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </section>
    );
}
