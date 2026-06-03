import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm<{
        file: File | null;
        tag: string;
    }>({
        file: null,
        tag: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('files.store'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout header="Upload File">
            <Head title="Upload File" />
            <PageHeader title="Upload File" />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="max-w-xl space-y-4">
                        <div>
                            <Label htmlFor="file">File</Label>
                            <Input
                                id="file"
                                type="file"
                                className="mt-1"
                                onChange={(e) =>
                                    setData('file', e.target.files?.[0] ?? null)
                                }
                            />
                            <InputError
                                message={errors.file}
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label htmlFor="tag">Tag (optional)</Label>
                            <Input
                                id="tag"
                                value={data.tag}
                                onChange={(e) => setData('tag', e.target.value)}
                                className="mt-1"
                            />
                            <InputError message={errors.tag} className="mt-1" />
                        </div>
                        <Button type="submit" disabled={processing}>
                            Upload
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
