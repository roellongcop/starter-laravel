import { useForm } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { type AdminTheme } from '@/types';

type Row = { key: string; value: string };

function toRows(obj?: Record<string, string>): Row[] {
    return Object.entries(obj ?? {}).map(([key, value]) => ({ key, value }));
}

function toObject(rows: Row[]): Record<string, string> {
    return rows.reduce<Record<string, string>>((acc, { key, value }) => {
        if (key.trim()) acc[key.trim()] = value;
        return acc;
    }, {});
}

function TokenEditor({
    title,
    rows,
    onChange,
}: {
    title: string;
    rows: Row[];
    onChange: (rows: Row[]) => void;
}) {
    return (
        <div className="rounded-md border p-3">
            <div className="mb-2 flex items-center justify-between">
                <Label>{title}</Label>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={() => onChange([...rows, { key: '', value: '' }])}
                >
                    <Plus className="h-4 w-4" /> Token
                </Button>
            </div>
            <div className="space-y-2">
                {rows.map((row, i) => (
                    <div key={i} className="flex items-center gap-2">
                        <Input
                            placeholder="--primary"
                            value={row.key}
                            onChange={(e) =>
                                onChange(
                                    rows.map((r, idx) =>
                                        idx === i
                                            ? { ...r, key: e.target.value }
                                            : r,
                                    ),
                                )
                            }
                        />
                        <Input
                            placeholder="222 47% 11%"
                            value={row.value}
                            onChange={(e) =>
                                onChange(
                                    rows.map((r, idx) =>
                                        idx === i
                                            ? { ...r, value: e.target.value }
                                            : r,
                                    ),
                                )
                            }
                        />
                        <span
                            className="h-8 w-8 shrink-0 rounded border"
                            style={{ background: `hsl(${row.value})` }}
                            aria-hidden
                        />
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            onClick={() =>
                                onChange(rows.filter((_, idx) => idx !== i))
                            }
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    </div>
                ))}
            </div>
        </div>
    );
}

export default function ThemeForm({ theme }: { theme?: AdminTheme }) {
    const editing = Boolean(theme);

    const { data, setData, post, patch, transform, processing, errors } =
        useForm({
            name: theme?.name ?? '',
            description: theme?.description ?? '',
            is_default: theme?.is_default ?? false,
            light: toRows(theme?.tokens?.light),
            dark: toRows(theme?.tokens?.dark),
        });

    transform((d) => ({
        name: d.name,
        description: d.description,
        is_default: d.is_default,
        tokens: { light: toObject(d.light), dark: toObject(d.dark) },
    }));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (editing && theme) {
            patch(route('themes.update', theme.id));
        } else {
            post(route('themes.store'));
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid max-w-2xl gap-4 sm:grid-cols-2">
                <div>
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        className="mt-1"
                    />
                    <InputError message={errors.name} className="mt-1" />
                </div>
                <div>
                    <Label htmlFor="description">Description</Label>
                    <Input
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        className="mt-1"
                    />
                </div>
            </div>

            <label className="flex max-w-2xl items-center justify-between rounded-md border p-3">
                <span className="text-sm">Set as default theme</span>
                <Switch
                    checked={data.is_default}
                    onCheckedChange={(c) => setData('is_default', c)}
                />
            </label>

            <div className="grid gap-4 lg:grid-cols-2">
                <TokenEditor
                    title="Light tokens"
                    rows={data.light}
                    onChange={(r) => setData('light', r)}
                />
                <TokenEditor
                    title="Dark tokens"
                    rows={data.dark}
                    onChange={(r) => setData('dark', r)}
                />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create theme'}
            </Button>
        </form>
    );
}
