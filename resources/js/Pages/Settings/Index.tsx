import { Head, router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

import ImagePicker, { PickedImage } from '@/Components/ImagePicker';
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
import { Switch } from '@/Components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface Props {
    settings: {
        system: Record<string, unknown>;
        email: Record<string, unknown>;
        image: Record<string, unknown>;
    };
    can: { update: boolean };
}

function Row({
    label,
    htmlFor,
    error,
    children,
}: {
    label: string;
    htmlFor?: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-1.5">
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

function SystemTab({ data: init }: { data: Record<string, unknown> }) {
    const { data, setData, put, processing, errors } = useForm({
        app_name: String(init.app_name ?? ''),
        timezone: String(init.timezone ?? 'UTC'),
        pagination_size: Number(init.pagination_size ?? 20),
        auto_logout_seconds: Number(init.auto_logout_seconds ?? 0),
        whitelist_ip_only: Boolean(init.whitelist_ip_only),
        default_theme: String(init.default_theme ?? 'system'),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('settings.update', 'system'), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid max-w-xl gap-4">
            <Row
                label="Application name"
                htmlFor="app_name"
                error={errors.app_name}
            >
                <Input
                    id="app_name"
                    value={data.app_name}
                    onChange={(e) => setData('app_name', e.target.value)}
                />
            </Row>
            <Row label="Timezone" htmlFor="timezone" error={errors.timezone}>
                <Input
                    id="timezone"
                    value={data.timezone}
                    onChange={(e) => setData('timezone', e.target.value)}
                />
            </Row>
            <div className="grid grid-cols-2 gap-4">
                <Row
                    label="Pagination size"
                    htmlFor="pagination_size"
                    error={errors.pagination_size}
                >
                    <Input
                        id="pagination_size"
                        type="number"
                        value={data.pagination_size}
                        onChange={(e) =>
                            setData('pagination_size', Number(e.target.value))
                        }
                    />
                </Row>
                <Row
                    label="Auto-logout (seconds, 0 = off)"
                    htmlFor="auto_logout_seconds"
                    error={errors.auto_logout_seconds}
                >
                    <Input
                        id="auto_logout_seconds"
                        type="number"
                        value={data.auto_logout_seconds}
                        onChange={(e) =>
                            setData(
                                'auto_logout_seconds',
                                Number(e.target.value),
                            )
                        }
                    />
                </Row>
            </div>
            <Row label="Default theme" error={errors.default_theme}>
                <Select
                    value={data.default_theme}
                    onValueChange={(v) => setData('default_theme', v)}
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="system">System</SelectItem>
                        <SelectItem value="light">Light</SelectItem>
                        <SelectItem value="dark">Dark</SelectItem>
                    </SelectContent>
                </Select>
            </Row>
            <label className="flex items-center justify-between rounded-md border p-3">
                <span className="text-sm">Whitelisted IPs only</span>
                <Switch
                    checked={data.whitelist_ip_only}
                    onCheckedChange={(c) => setData('whitelist_ip_only', c)}
                />
            </label>
            <div>
                <Button type="submit" disabled={processing}>
                    Save system settings
                </Button>
            </div>
        </form>
    );
}

function EmailTab({ data: init }: { data: Record<string, unknown> }) {
    const { data, setData, put, processing, errors } = useForm({
        from_address: String(init.from_address ?? ''),
        from_name: String(init.from_name ?? ''),
        smtp_host: String(init.smtp_host ?? ''),
        smtp_port: Number(init.smtp_port ?? 587),
        smtp_username: String(init.smtp_username ?? ''),
        smtp_password: '',
        smtp_encryption: String(init.smtp_encryption ?? ''),
    });

    const [testing, setTesting] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('settings.update', 'email'), { preserveScroll: true });
    };

    // Sends a test email to the current user using the *saved* settings (toasts
    // success/error via the shared flash bag). Save before testing.
    const sendTest = () => {
        setTesting(true);
        router.post(
            route('settings.email.test'),
            {},
            { preserveScroll: true, onFinish: () => setTesting(false) },
        );
    };

    return (
        <form onSubmit={submit} className="grid max-w-xl gap-4">
            <div className="grid grid-cols-2 gap-4">
                <Row
                    label="From address"
                    htmlFor="from_address"
                    error={errors.from_address}
                >
                    <Input
                        id="from_address"
                        value={data.from_address}
                        onChange={(e) =>
                            setData('from_address', e.target.value)
                        }
                    />
                </Row>
                <Row
                    label="From name"
                    htmlFor="from_name"
                    error={errors.from_name}
                >
                    <Input
                        id="from_name"
                        value={data.from_name}
                        onChange={(e) => setData('from_name', e.target.value)}
                    />
                </Row>
            </div>
            <div className="grid grid-cols-2 gap-4">
                <Row
                    label="SMTP host"
                    htmlFor="smtp_host"
                    error={errors.smtp_host}
                >
                    <Input
                        id="smtp_host"
                        value={data.smtp_host}
                        onChange={(e) => setData('smtp_host', e.target.value)}
                    />
                </Row>
                <Row
                    label="SMTP port"
                    htmlFor="smtp_port"
                    error={errors.smtp_port}
                >
                    <Input
                        id="smtp_port"
                        type="number"
                        value={data.smtp_port}
                        onChange={(e) =>
                            setData('smtp_port', Number(e.target.value))
                        }
                    />
                </Row>
            </div>
            <div className="grid grid-cols-2 gap-4">
                <Row
                    label="SMTP username"
                    htmlFor="smtp_username"
                    error={errors.smtp_username}
                >
                    <Input
                        id="smtp_username"
                        value={data.smtp_username}
                        onChange={(e) =>
                            setData('smtp_username', e.target.value)
                        }
                    />
                </Row>
                <Row
                    label="SMTP password (blank = keep)"
                    htmlFor="smtp_password"
                    error={errors.smtp_password}
                >
                    <Input
                        id="smtp_password"
                        type="password"
                        value={data.smtp_password}
                        onChange={(e) =>
                            setData('smtp_password', e.target.value)
                        }
                        autoComplete="new-password"
                    />
                </Row>
            </div>
            <Row label="Encryption" error={errors.smtp_encryption}>
                <Select
                    value={data.smtp_encryption || 'none'}
                    onValueChange={(v) =>
                        setData('smtp_encryption', v === 'none' ? '' : v)
                    }
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">None</SelectItem>
                        <SelectItem value="tls">TLS</SelectItem>
                        <SelectItem value="ssl">SSL</SelectItem>
                    </SelectContent>
                </Select>
            </Row>
            <div className="flex gap-2">
                <Button type="submit" disabled={processing}>
                    Save email settings
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    onClick={sendTest}
                    disabled={testing}
                >
                    {testing ? 'Sending…' : 'Send test email'}
                </Button>
            </div>
        </form>
    );
}

// A single brand-image slot: preview + pick (via <ImagePicker>) + remove. The
// picker uploads immediately and returns { token, url }; we store the token in
// the form and use the url for an instant preview.
function BrandSlot({
    label,
    hint,
    initialUrl,
    aspectRatio,
    onChange,
}: {
    label: string;
    hint: string;
    initialUrl: string | null;
    aspectRatio?: number;
    onChange: (token: string | null) => void;
}) {
    const [open, setOpen] = useState(false);
    const [preview, setPreview] = useState<string | null>(initialUrl);

    const picked = (image: PickedImage) => {
        setPreview(image.url);
        onChange(image.token);
    };

    const remove = () => {
        setPreview(null);
        onChange(null);
    };

    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <p className="text-sm text-muted-foreground">{hint}</p>
            <div className="flex items-center gap-4">
                <div className="flex h-20 w-20 items-center justify-center overflow-hidden rounded-md border bg-muted/30">
                    {preview ? (
                        <img
                            src={preview}
                            alt={label}
                            className="max-h-full max-w-full object-contain"
                        />
                    ) : (
                        <span className="text-xs text-muted-foreground">
                            None
                        </span>
                    )}
                </div>
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setOpen(true)}
                    >
                        {preview ? 'Change' : 'Choose'}
                    </Button>
                    {preview && (
                        <Button type="button" variant="ghost" onClick={remove}>
                            Remove
                        </Button>
                    )}
                </div>
            </div>
            <ImagePicker
                open={open}
                onOpenChange={setOpen}
                onPicked={picked}
                aspectRatio={aspectRatio}
                title={`Choose ${label.toLowerCase()}`}
            />
        </div>
    );
}

function ImageTab({ data: init }: { data: Record<string, unknown> }) {
    const { setData, put, processing } = useForm({
        favicon_token: (init.favicon_token as string | null) ?? null,
        square_logo_token: (init.square_logo_token as string | null) ?? null,
        landscape_logo_token:
            (init.landscape_logo_token as string | null) ?? null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('settings.update', 'image'), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid max-w-xl gap-6">
            <BrandSlot
                label="Favicon"
                hint="Square image shown in the browser tab."
                initialUrl={(init.favicon_url as string | null) ?? null}
                aspectRatio={1}
                onChange={(token) => setData('favicon_token', token)}
            />
            <BrandSlot
                label="Square logo"
                hint="Square logo shown in the app header."
                initialUrl={(init.square_logo_url as string | null) ?? null}
                aspectRatio={1}
                onChange={(token) => setData('square_logo_token', token)}
            />
            <BrandSlot
                label="Landscape logo"
                hint="Wide logo shown on the login screen."
                initialUrl={(init.landscape_logo_url as string | null) ?? null}
                onChange={(token) => setData('landscape_logo_token', token)}
            />
            <div>
                <Button type="submit" disabled={processing}>
                    Save image settings
                </Button>
            </div>
        </form>
    );
}

const SETTINGS_TABS = ['system', 'email', 'image'];

// Restore the active tab from the URL (?tab=) so a refresh keeps the user on it.
function initialTab(): string {
    if (typeof window === 'undefined') return 'system';
    const tab = new URLSearchParams(window.location.search).get('tab');
    return tab && SETTINGS_TABS.includes(tab) ? tab : 'system';
}

export default function Index({ settings }: Props) {
    const [tab, setTab] = useState(initialTab);

    const onTabChange = (value: string) => {
        setTab(value);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', value);
        // replaceState (not Inertia) so the choice survives refresh without a visit.
        window.history.replaceState({}, '', url);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Settings" />
            <PageHeader
                title="Settings"
                description="Application configuration, stored as typed settings groups."
            />
            <Card>
                <CardContent className="pt-6">
                    <Tabs value={tab} onValueChange={onTabChange}>
                        <TabsList>
                            <TabsTrigger value="system">System</TabsTrigger>
                            <TabsTrigger value="email">Email</TabsTrigger>
                            <TabsTrigger value="image">Image</TabsTrigger>
                        </TabsList>
                        <TabsContent value="system">
                            <SystemTab data={settings.system} />
                        </TabsContent>
                        <TabsContent value="email">
                            <EmailTab data={settings.email} />
                        </TabsContent>
                        <TabsContent value="image">
                            <ImageTab data={settings.image} />
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
