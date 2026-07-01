import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

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

interface Props {
    formats: string[];
    resources: string[];
}

export default function Create({ formats, resources }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        resource: string;
        format: string;
        filters: { search: string };
    }>({
        resource: resources[0] ?? 'users',
        format: formats[0] ?? 'csv',
        filters: { search: '' },
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('exports.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="New Export" />
            <PageHeader
                title="New Export"
                breadcrumbs={[
                    { label: 'My Exports', href: route('exports.index') },
                    { label: 'New Export' },
                ]}
            />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="max-w-md space-y-4">
                        <div>
                            <Label required>Resource</Label>
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
                            <InputError
                                message={errors.resource}
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label required>Format</Label>
                            <Select
                                value={data.format}
                                onValueChange={(v) => setData('format', v)}
                            >
                                <SelectTrigger className="mt-1">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {formats.map((f) => (
                                        <SelectItem key={f} value={f}>
                                            {f.toUpperCase()}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={errors.format}
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label htmlFor="search">
                                Search filter (optional)
                            </Label>
                            <Input
                                id="search"
                                value={data.filters.search}
                                onChange={(e) =>
                                    setData('filters', {
                                        search: e.target.value,
                                    })
                                }
                                className="mt-1"
                                placeholder="Limit to name/email containing…"
                            />
                        </div>
                        <Button type="submit" disabled={processing}>
                            Queue export
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
