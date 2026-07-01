import { router, useForm } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import Avatar from '@/Components/Avatar';
import ConfirmDialog from '@/Components/ConfirmDialog';
import DocumentList from '@/Components/DocumentList';
import FileDropzone from '@/Components/FileDropzone';
import ImagePicker, { type PickedImage } from '@/Components/ImagePicker';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    type AdminDocument,
    type AdminUser,
    type CursorResponse,
    type SelectOption,
    type UserMetaRow,
} from '@/types';

interface Props {
    user?: AdminUser;
    roleOptions: string[];
    statusOptions: SelectOption[];
    documents?: CursorResponse<AdminDocument>;
}

export default function UserForm({
    user,
    roleOptions,
    statusOptions,
    documents,
}: Props) {
    const editing = Boolean(user);

    const { data, setData, post, patch, processing, errors } = useForm<{
        name: string;
        email: string;
        username: string;
        password: string;
        password_confirmation: string;
        password_hint: string;
        user_status: string;
        avatar_file_token: string | null;
        roles: string[];
        meta: UserMetaRow[];
    }>({
        name: user?.name ?? '',
        email: user?.email ?? '',
        username: user?.username ?? '',
        password: '',
        password_confirmation: '',
        password_hint: user?.password_hint ?? '',
        user_status: user?.user_status ?? 'Active',
        avatar_file_token: null,
        roles: user?.roles ?? [],
        meta: user?.meta ?? [],
    });

    const [pickerOpen, setPickerOpen] = useState(false);
    const [preview, setPreview] = useState<string | null>(
        user?.avatar_url ?? null,
    );

    const onPicked = (image: PickedImage) => {
        setData('avatar_file_token', image.token);
        setPreview(image.url);
    };

    const [deletingDoc, setDeletingDoc] = useState<AdminDocument | null>(null);

    const refreshDocuments = () => router.reload({ only: ['documents'] });

    const destroyDocument = () => {
        if (!deletingDoc) return;
        router.delete(route('documents.destroy', deletingDoc.token), {
            preserveScroll: true,
            onFinish: () => setDeletingDoc(null),
        });
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (editing && user) {
            patch(route('users.update', user.token));
        } else {
            post(route('users.store'));
        }
    };

    const toggleRole = (role: string) =>
        setData(
            'roles',
            data.roles.includes(role)
                ? data.roles.filter((r) => r !== role)
                : [...data.roles, role],
        );

    const setMeta = (i: number, field: keyof UserMetaRow, value: string) =>
        setData(
            'meta',
            data.meta.map((row, idx) =>
                idx === i ? { ...row, [field]: value } : row,
            ),
        );

    return (
        <>
            <form onSubmit={submit} className="max-w-2xl space-y-6">
                <div className="flex items-center gap-4">
                    <Avatar
                        name={data.name || user?.name || '?'}
                        src={preview}
                        size={64}
                    />
                    <div>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setPickerOpen(true)}
                        >
                            Choose photo
                        </Button>
                        <InputError
                            message={errors.avatar_file_token}
                            className="mt-1"
                        />
                    </div>
                </div>

                <ImagePicker
                    open={pickerOpen}
                    onOpenChange={setPickerOpen}
                    onPicked={onPicked}
                    aspectRatio={1}
                    title="User photo"
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label htmlFor="name" required>
                            Name
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1"
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>
                    <div>
                        <Label htmlFor="email" required>
                            Email
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="mt-1"
                        />
                        <InputError message={errors.email} className="mt-1" />
                    </div>
                    <div>
                        <Label htmlFor="username">Username</Label>
                        <Input
                            id="username"
                            value={data.username}
                            onChange={(e) =>
                                setData('username', e.target.value)
                            }
                            className="mt-1"
                        />
                        <InputError
                            message={errors.username}
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label htmlFor="user_status" required>
                            Status
                        </Label>
                        <Select
                            value={data.user_status}
                            onValueChange={(v) => setData('user_status', v)}
                        >
                            <SelectTrigger id="user_status" className="mt-1">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {statusOptions.map((o) => (
                                    <SelectItem
                                        key={o.value}
                                        value={String(o.value)}
                                    >
                                        {o.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError
                            message={errors.user_status}
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label htmlFor="password" required>
                            Password {editing && '(leave blank to keep)'}
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            className="mt-1"
                            autoComplete="new-password"
                        />
                        <InputError
                            message={errors.password}
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label htmlFor="password_confirmation">
                            Confirm password
                        </Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            className="mt-1"
                            autoComplete="new-password"
                        />
                    </div>
                    <div className="sm:col-span-2">
                        <Label htmlFor="password_hint">Password hint</Label>
                        <Input
                            id="password_hint"
                            value={data.password_hint}
                            onChange={(e) =>
                                setData('password_hint', e.target.value)
                            }
                            className="mt-1"
                        />
                        <InputError
                            message={errors.password_hint}
                            className="mt-1"
                        />
                    </div>
                </div>

                <div>
                    <Label>Roles</Label>
                    <div className="mt-2 flex flex-wrap gap-3">
                        {roleOptions.map((role) => (
                            <label
                                key={role}
                                className="flex items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    checked={data.roles.includes(role)}
                                    onCheckedChange={() => toggleRole(role)}
                                />
                                {role}
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.roles} className="mt-1" />
                </div>

                <div>
                    <div className="flex items-center justify-between">
                        <Label>Custom fields (meta)</Label>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() =>
                                setData('meta', [
                                    ...data.meta,
                                    { key: '', value: '' },
                                ])
                            }
                        >
                            <Plus className="h-4 w-4" /> Add field
                        </Button>
                    </div>
                    <div className="mt-2 space-y-2">
                        {data.meta.map((row, i) => (
                            <div key={i} className="flex gap-2">
                                <Input
                                    placeholder="key"
                                    value={row.key}
                                    onChange={(e) =>
                                        setMeta(i, 'key', e.target.value)
                                    }
                                />
                                <Input
                                    placeholder="value"
                                    value={row.value ?? ''}
                                    onChange={(e) =>
                                        setMeta(i, 'value', e.target.value)
                                    }
                                />
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    title="Remove field"
                                    aria-label="Remove field"
                                    onClick={() =>
                                        setData(
                                            'meta',
                                            data.meta.filter(
                                                (_, idx) => idx !== i,
                                            ),
                                        )
                                    }
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={processing}>
                        {editing ? 'Save changes' : 'Create user'}
                    </Button>
                </div>
            </form>

            {user && documents && (
                <div className="mt-8 max-w-2xl space-y-4 border-t pt-6">
                    <div>
                        <h3 className="text-base font-medium">Documents</h3>
                        <p className="text-sm text-muted-foreground">
                            Upload PDF or Word documents to this user&apos;s
                            account.
                        </p>
                    </div>
                    <FileDropzone
                        uploadUrl={route('documents.store')}
                        data={{ user_token: user.token }}
                        hint="PDF, DOC or DOCX — up to 10 MB each"
                        onUploaded={refreshDocuments}
                    />
                    <DocumentList
                        documents={documents}
                        onDelete={setDeletingDoc}
                    />
                </div>
            )}

            <ConfirmDialog
                open={deletingDoc !== null}
                onOpenChange={(o) => !o && setDeletingDoc(null)}
                title={`Delete ${deletingDoc?.name}?`}
                description="This permanently removes the document."
                confirmLabel="Delete"
                destructive
                onConfirm={destroyDocument}
            />
        </>
    );
}
