import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import BackButton from '@/Components/BackButton';
import InputError from '@/Components/InputError';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Create({ resources }: { resources: string[] }) {
    const { data, setData, post, processing, errors } = useForm<{
        file: File | null;
        resource: string;
    }>({
        file: null,
        resource: resources[0] ?? 'users',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('imports.store'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="New Import" />
            <PageHeader
                title="New Import"
                description="Upload a CSV/XLSX with name + email columns."
                actions={<BackButton fallback={route('imports.index')} />}
            />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="max-w-md space-y-4">
                        <div>
                            <Label>Resource</Label>
                            <Select
                                value={data.resource}
                                onValueChange={(v) => setData('resource', v)}
                            >
                                <SelectTrigger className="mt-1">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {resources.map((r) => (
                                        <SelectItem key={r} value={r}>
                                            {r}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
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
                        <Button type="submit" disabled={processing}>
                            Upload &amp; preview
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
