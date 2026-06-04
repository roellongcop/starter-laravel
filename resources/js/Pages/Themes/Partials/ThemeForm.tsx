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

// Default token sets for a brand-new theme — mirrors database/seeders/ThemeSeeder
// so a new theme starts from the canonical "Keen" palette with every key present.
const DEFAULT_LIGHT: Row[] = [
    { key: '--background', value: '0 0% 100%' },
    { key: '--foreground', value: '222.2 84% 4.9%' },
    { key: '--primary', value: '222.2 47.4% 11.2%' },
    { key: '--primary-foreground', value: '210 40% 98%' },
    { key: '--secondary', value: '210 40% 96.1%' },
    { key: '--muted', value: '210 40% 96.1%' },
    { key: '--accent', value: '210 40% 96.1%' },
    { key: '--destructive', value: '0 84.2% 60.2%' },
    { key: '--border', value: '214.3 31.8% 91.4%' },
    { key: '--ring', value: '222.2 84% 4.9%' },
];

const DEFAULT_DARK: Row[] = [
    { key: '--background', value: '222.2 84% 4.9%' },
    { key: '--foreground', value: '210 40% 98%' },
    { key: '--primary', value: '210 40% 98%' },
    { key: '--primary-foreground', value: '222.2 47.4% 11.2%' },
    { key: '--secondary', value: '217.2 32.6% 17.5%' },
    { key: '--muted', value: '217.2 32.6% 17.5%' },
    { key: '--accent', value: '217.2 32.6% 17.5%' },
    { key: '--destructive', value: '0 62.8% 30.6%' },
    { key: '--border', value: '217.2 32.6% 17.5%' },
    { key: '--ring', value: '212.7 26.8% 83.9%' },
];

/**
 * Convert a shadcn HSL triplet ("222.2 47.4% 11.2%") to a #rrggbb hex string for
 * the native color input. Returns black on an unparseable value.
 */
function hslTripletToHex(triplet: string): string {
    const m = triplet.trim().match(/^([\d.]+)\s+([\d.]+)%?\s+([\d.]+)%?$/);
    if (!m) return '#000000';
    const h = parseFloat(m[1]);
    const s = parseFloat(m[2]) / 100;
    const l = parseFloat(m[3]) / 100;
    const c = (1 - Math.abs(2 * l - 1)) * s;
    const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
    const min = l - c / 2;
    let r = 0;
    let g = 0;
    let b = 0;
    if (h < 60) [r, g, b] = [c, x, 0];
    else if (h < 120) [r, g, b] = [x, c, 0];
    else if (h < 180) [r, g, b] = [0, c, x];
    else if (h < 240) [r, g, b] = [0, x, c];
    else if (h < 300) [r, g, b] = [x, 0, c];
    else [r, g, b] = [c, 0, x];
    const hex = (n: number) =>
        Math.round((n + min) * 255)
            .toString(16)
            .padStart(2, '0');
    return `#${hex(r)}${hex(g)}${hex(b)}`;
}

/** Convert a #rrggbb hex string back to the shadcn HSL triplet format. */
function hexToHslTriplet(hex: string): string {
    const m = hex
        .replace('#', '')
        .match(/^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i);
    if (!m) return '0 0% 0%';
    const r = parseInt(m[1], 16) / 255;
    const g = parseInt(m[2], 16) / 255;
    const b = parseInt(m[3], 16) / 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const d = max - min;
    const l = (max + min) / 2;
    let h = 0;
    let s = 0;
    if (d !== 0) {
        s = d / (1 - Math.abs(2 * l - 1));
        if (max === r) h = ((g - b) / d) % 6;
        else if (max === g) h = (b - r) / d + 2;
        else h = (r - g) / d + 4;
        h *= 60;
        if (h < 0) h += 360;
    }
    const round = (n: number) => Math.round(n * 10) / 10;
    return `${round(h)} ${round(s * 100)}% ${round(l * 100)}%`;
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
    const setRow = (i: number, patch: Partial<Row>) =>
        onChange(rows.map((r, idx) => (idx === i ? { ...r, ...patch } : r)));

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
                            onChange={(e) => setRow(i, { key: e.target.value })}
                        />
                        <Input
                            placeholder="222 47% 11%"
                            value={row.value}
                            onChange={(e) =>
                                setRow(i, { value: e.target.value })
                            }
                        />
                        <label
                            className="relative h-8 w-8 shrink-0 cursor-pointer rounded border"
                            style={{ background: `hsl(${row.value})` }}
                            title="Pick a color"
                        >
                            <span className="sr-only">Pick a color</span>
                            <input
                                type="color"
                                className="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                value={hslTripletToHex(row.value)}
                                onChange={(e) =>
                                    setRow(i, {
                                        value: hexToHslTriplet(e.target.value),
                                    })
                                }
                            />
                        </label>
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
            light: theme ? toRows(theme.tokens?.light) : DEFAULT_LIGHT,
            dark: theme ? toRows(theme.tokens?.dark) : DEFAULT_DARK,
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
            patch(route('themes.update', theme.token));
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
