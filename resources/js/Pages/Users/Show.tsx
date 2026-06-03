import { Head, Link } from '@inertiajs/react';

import Avatar from '@/Components/Avatar';
import Can from '@/Components/Can';
import DocumentList from '@/Components/DocumentList';
import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    type AdminDocument,
    type AdminUser,
    type CursorResponse,
} from '@/types';

function Field({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div>
            <dt className="text-xs uppercase tracking-wide text-muted-foreground">
                {label}
            </dt>
            <dd className="mt-1 text-sm text-foreground">{value || '—'}</dd>
        </div>
    );
}

export default function Show({
    user,
    documents,
}: {
    user: AdminUser;
    documents: CursorResponse<AdminDocument>;
}) {
    return (
        <AuthenticatedLayout>
            <Head title={user.name} />

            <PageHeader
                title={user.name}
                description={user.email}
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <Link href={route('users.index')}>Back</Link>
                        </Button>
                        <Can ability="users.update">
                            <Button asChild>
                                <Link href={route('users.edit', user.id)}>
                                    Edit
                                </Link>
                            </Button>
                        </Can>
                    </>
                }
            />

            <div className="mb-6 flex items-center gap-4">
                <Avatar name={user.name} src={user.avatar_url} size={72} />
                <div>
                    <p className="text-lg font-medium">{user.name}</p>
                    <p className="text-sm text-muted-foreground">
                        {user.email}
                    </p>
                </div>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-2 gap-4">
                            <Field label="Username" value={user.username} />
                            <Field
                                label="Status"
                                value={
                                    <Badge variant="secondary">
                                        {user.user_status}
                                    </Badge>
                                }
                            />
                            <Field
                                label="Roles"
                                value={user.roles.join(', ')}
                            />
                            <Field
                                label="Password hint"
                                value={user.password_hint}
                            />
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Custom fields</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {user.meta && user.meta.length > 0 ? (
                            <dl className="space-y-2">
                                {user.meta.map((m) => (
                                    <div
                                        key={m.key}
                                        className="flex justify-between border-b pb-1 text-sm"
                                    >
                                        <span className="font-medium">
                                            {m.key}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {m.value || '—'}
                                        </span>
                                    </div>
                                ))}
                            </dl>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No custom fields.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Documents</CardTitle>
                </CardHeader>
                <CardContent>
                    <DocumentList
                        documents={documents}
                        emptyText="No documents for this user."
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
