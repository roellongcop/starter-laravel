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
import { Switch } from '@/Components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface Props {
    settings: {
        system: Record<string, unknown>;
        email: Record<string, unknown>;
        image: Record<string, unknown>;
        notification: { templates: Record<string, string> };
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
        enable_visitor: Boolean(init.enable_visitor),
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
                <span className="text-sm">Enable visitor tracking</span>
                <Switch
                    checked={data.enable_visitor}
                    onCheckedChange={(c) => setData('enable_visitor', c)}
                />
            </label>
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

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('settings.update', 'email'), { preserveScroll: true });
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
            <div>
                <Button type="submit" disabled={processing}>
                    Save email settings
                </Button>
            </div>
        </form>
    );
}

function ImageTab({ data: init }: { data: Record<string, unknown> }) {
    const { data, setData, put, processing, errors } = useForm({
        max_width: Number(init.max_width ?? 2000),
        max_height: Number(init.max_height ?? 2000),
        allowed_types: (init.allowed_types as string[]) ?? [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('settings.update', 'image'), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid max-w-xl gap-4">
            <div className="grid grid-cols-2 gap-4">
                <Row
                    label="Max width (px)"
                    htmlFor="max_width"
                    error={errors.max_width}
                >
                    <Input
                        id="max_width"
                        type="number"
                        value={data.max_width}
                        onChange={(e) =>
                            setData('max_width', Number(e.target.value))
                        }
                    />
                </Row>
                <Row
                    label="Max height (px)"
                    htmlFor="max_height"
                    error={errors.max_height}
                >
                    <Input
                        id="max_height"
                        type="number"
                        value={data.max_height}
                        onChange={(e) =>
                            setData('max_height', Number(e.target.value))
                        }
                    />
                </Row>
            </div>
            <Row
                label="Allowed types (comma separated)"
                htmlFor="allowed_types"
                error={errors.allowed_types}
            >
                <Input
                    id="allowed_types"
                    value={data.allowed_types.join(', ')}
                    onChange={(e) =>
                        setData(
                            'allowed_types',
                            e.target.value
                                .split(',')
                                .map((s) => s.trim())
                                .filter(Boolean),
                        )
                    }
                />
            </Row>
            <div>
                <Button type="submit" disabled={processing}>
                    Save image settings
                </Button>
            </div>
        </form>
    );
}

function NotificationTab({ templates }: { templates: Record<string, string> }) {
    const { data, setData, put, processing } = useForm({
        templates: { ...templates },
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('settings.update', 'notification'), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid max-w-xl gap-4">
            {Object.entries(data.templates).map(([type, tpl]) => (
                <Row key={type} label={`${type} template`}>
                    <Textarea
                        value={tpl}
                        onChange={(e) =>
                            setData('templates', {
                                ...data.templates,
                                [type]: e.target.value,
                            })
                        }
                    />
                </Row>
            ))}
            <div>
                <Button type="submit" disabled={processing}>
                    Save notification settings
                </Button>
            </div>
        </form>
    );
}

export default function Index({ settings }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="Settings" />
            <PageHeader
                title="Settings"
                description="Application configuration, stored as typed settings groups."
            />
            <Card>
                <CardContent className="pt-6">
                    <Tabs defaultValue="system">
                        <TabsList>
                            <TabsTrigger value="system">System</TabsTrigger>
                            <TabsTrigger value="email">Email</TabsTrigger>
                            <TabsTrigger value="image">Image</TabsTrigger>
                            <TabsTrigger value="notification">
                                Notifications
                            </TabsTrigger>
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
                        <TabsContent value="notification">
                            <NotificationTab
                                templates={settings.notification.templates}
                            />
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
