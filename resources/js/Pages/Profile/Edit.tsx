import PageHeader from '@/Components/PageHeader.js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminDocument, type CursorResponse, PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdateAvatarForm from './Partials/UpdateAvatarForm';
import UpdateDocumentsForm from './Partials/UpdateDocumentsForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
    passwordHint,
    documents,
}: PageProps<{
    mustVerifyEmail: boolean;
    status?: string;
    passwordHint?: string | null;
    documents: CursorResponse<AdminDocument>;
}>) {
    return (
        <AuthenticatedLayout>
            <Head title="Profile" />
            <PageHeader title="Profile" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <UpdateAvatarForm className="max-w-xl" />
                </div>

                <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        passwordHint={passwordHint ?? ''}
                        className="max-w-xl"
                    />
                </div>

                <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <UpdateDocumentsForm documents={documents} />
                </div>

                <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>

                <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <DeleteUserForm className="max-w-xl" />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
