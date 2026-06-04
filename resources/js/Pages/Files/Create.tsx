import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { type Accept } from 'react-dropzone';

import BackButton from '@/Components/BackButton';
import FileDropzone from '@/Components/FileDropzone';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

// Client-side dialog/drag hint only — StoreFileRequest is authoritative.
const FILE_ACCEPT: Accept = {
    'image/jpeg': ['.jpg', '.jpeg'],
    'image/png': ['.png'],
    'image/webp': ['.webp'],
    'image/gif': ['.gif'],
    'application/pdf': ['.pdf'],
    'application/msword': ['.doc'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': [
        '.docx',
    ],
    'text/csv': ['.csv'],
    'application/vnd.ms-excel': ['.xls'],
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': [
        '.xlsx',
    ],
};

export default function Create() {
    const [tag, setTag] = useState('');
    const [uploaded, setUploaded] = useState(0);

    return (
        <AuthenticatedLayout>
            <Head title="Upload Files" />
            <PageHeader
                title="Upload Files"
                actions={<BackButton fallback={route('files.index')} />}
            />
            <Card>
                <CardContent className="space-y-4 pt-6">
                    <div className="max-w-xl">
                        <Label htmlFor="tag">Tag (optional)</Label>
                        <Input
                            id="tag"
                            value={tag}
                            onChange={(e) => setTag(e.target.value)}
                            className="mt-1"
                            placeholder="Applied to every file in this batch"
                        />
                    </div>

                    <FileDropzone
                        uploadUrl={route('files.store')}
                        accept={FILE_ACCEPT}
                        multiple
                        field="file"
                        data={tag ? { tag } : undefined}
                        hint="Images, PDF, Word, CSV or Excel — up to 10 MB each"
                        onUploaded={() => setUploaded((n) => n + 1)}
                    />

                    <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">
                            {uploaded > 0
                                ? `${uploaded} file${uploaded === 1 ? '' : 's'} uploaded`
                                : ''}
                        </span>
                        <Button
                            type="button"
                            onClick={() => router.get(route('files.index'))}
                        >
                            {uploaded > 0 ? 'View files' : 'Done'}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
