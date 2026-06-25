import { useForm } from '@inertiajs/react';
import { FileText, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import FileDropzone from '@/Components/FileDropzone';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { type AdminReferenceFile, type SelectOption } from '@/types';

interface UploadedFile {
    token: string;
    name: string;
}

interface Props {
    reference?: Pick<
        AdminReferenceFile,
        | 'token'
        | 'name'
        | 'description'
        | 'organization'
        | 'file_token'
        | 'file_name'
    >;
    organizations: SelectOption[];
    onSuccess?: () => void;
}

const ACCEPT = {
    'application/pdf': ['.pdf'],
    'application/msword': ['.doc'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': [
        '.docx',
    ],
    'application/vnd.ms-excel': ['.xls'],
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': [
        '.xlsx',
    ],
    'text/csv': ['.csv'],
    'image/png': ['.png'],
    'image/jpeg': ['.jpg', '.jpeg'],
    'image/gif': ['.gif'],
    'image/webp': ['.webp'],
};

export default function ReferenceFileForm({
    reference,
    organizations,
    onSuccess,
}: Props) {
    const editing = Boolean(reference);
    const [fileName, setFileName] = useState<string | null>(
        reference?.file_name ?? null,
    );

    const { data, setData, post, patch, processing, errors } = useForm({
        name: reference?.name ?? '',
        description: reference?.description ?? '',
        organization:
            reference?.organization ?? String(organizations[0]?.value ?? ''),
        file_token: reference?.file_token ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (editing && reference) {
            patch(route('reference-files.update', reference.token), options);
        } else {
            post(route('reference-files.store'), options);
        }
    };

    const onUploaded = (file: unknown) => {
        const uploaded = file as UploadedFile;
        setData('file_token', uploaded.token);
        setFileName(uploaded.name);
    };

    const removeFile = () => {
        setData('file_token', '');
        setFileName(null);
    };

    return (
        <form onSubmit={submit} className="max-w-xl space-y-4">
            <div>
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="mt-1"
                    placeholder="Company Handbook"
                />
                <InputError message={errors.name} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="description">Description</Label>
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    className="mt-1"
                    rows={2}
                    placeholder="Optional description"
                />
                <InputError message={errors.description} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="organization">Organization</Label>
                <Select
                    value={data.organization}
                    onValueChange={(v) => setData('organization', v)}
                >
                    <SelectTrigger id="organization" className="mt-1">
                        <SelectValue placeholder="Select an organization" />
                    </SelectTrigger>
                    <SelectContent>
                        {organizations.map((o) => (
                            <SelectItem key={o.value} value={String(o.value)}>
                                {o.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.organization} className="mt-1" />
            </div>

            <div>
                <Label>File</Label>
                {data.file_token && fileName ? (
                    <div className="mt-1 flex items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm">
                        <span className="flex items-center gap-2 truncate">
                            <FileText className="h-4 w-4 shrink-0" />
                            <span className="truncate">{fileName}</span>
                        </span>
                        <button
                            type="button"
                            onClick={removeFile}
                            className="text-muted-foreground hover:text-foreground"
                            aria-label="Remove file"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                ) : (
                    <div className="mt-1">
                        <FileDropzone
                            uploadUrl={route('reference-files.upload')}
                            multiple={false}
                            accept={ACCEPT}
                            hint="PDF, Office docs, CSV or images up to 10 MB"
                            onUploaded={onUploaded}
                        />
                    </div>
                )}
                <InputError message={errors.file_token} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create reference'}
            </Button>
        </form>
    );
}
