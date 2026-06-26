import * as React from 'react';

import { useTheme } from '@/Components/ThemeProvider';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';

type Mode = 'light' | 'dark';

/**
 * The full shadcn base token set, mirroring resources/css/app.css (:root and
 * [data-theme="dark"]). The theme form only edits a subset of these, so the
 * preview layers the edited tokens on top of this base to render a complete,
 * self-contained palette regardless of what theme the surrounding app is in.
 */
const BASE: Record<Mode, Record<string, string>> = {
    light: {
        '--background': '0 0% 100%',
        '--foreground': '222.2 84% 4.9%',
        '--card': '0 0% 100%',
        '--card-foreground': '222.2 84% 4.9%',
        '--popover': '0 0% 100%',
        '--popover-foreground': '222.2 84% 4.9%',
        '--primary': '222.2 47.4% 11.2%',
        '--primary-foreground': '210 40% 98%',
        '--secondary': '210 40% 96.1%',
        '--secondary-foreground': '222.2 47.4% 11.2%',
        '--muted': '210 40% 96.1%',
        '--muted-foreground': '215.4 16.3% 46.9%',
        '--accent': '210 40% 96.1%',
        '--accent-foreground': '222.2 47.4% 11.2%',
        '--destructive': '0 84.2% 60.2%',
        '--destructive-foreground': '210 40% 98%',
        '--border': '214.3 31.8% 91.4%',
        '--input': '214.3 31.8% 91.4%',
        '--ring': '222.2 84% 4.9%',
        '--radius': '0.5rem',
    },
    dark: {
        '--background': '222.2 84% 4.9%',
        '--foreground': '210 40% 98%',
        '--card': '222.2 84% 4.9%',
        '--card-foreground': '210 40% 98%',
        '--popover': '222.2 84% 4.9%',
        '--popover-foreground': '210 40% 98%',
        '--primary': '210 40% 98%',
        '--primary-foreground': '222.2 47.4% 11.2%',
        '--secondary': '217.2 32.6% 17.5%',
        '--secondary-foreground': '210 40% 98%',
        '--muted': '217.2 32.6% 17.5%',
        '--muted-foreground': '215 20.2% 65.1%',
        '--accent': '217.2 32.6% 17.5%',
        '--accent-foreground': '210 40% 98%',
        '--destructive': '0 62.8% 30.6%',
        '--destructive-foreground': '210 40% 98%',
        '--border': '217.2 32.6% 17.5%',
        '--input': '217.2 32.6% 17.5%',
        '--ring': '212.7 26.8% 83.9%',
        '--radius': '0.5rem',
    },
};

/**
 * Live, scoped preview of an edited theme. The form passes its current light /
 * dark token maps; this renders a gallery of real UI primitives whose CSS
 * variables are set inline on a single container, so edits reflect instantly
 * without touching the surrounding editor chrome. See docs/features/theming.md.
 */
export default function ThemePreview({
    light,
    dark,
}: {
    light: Record<string, string>;
    dark: Record<string, string>;
}) {
    const { resolvedTheme } = useTheme();
    const [mode, setMode] = React.useState<Mode>(resolvedTheme);

    const tokens = React.useMemo(
        () => ({ ...BASE[mode], ...(mode === 'dark' ? dark : light) }),
        [mode, light, dark],
    );

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="text-sm font-medium text-foreground">
                        Live preview
                    </h3>
                    <p className="truncate text-xs text-muted-foreground">
                        Edits apply instantly — scoped to this panel.
                    </p>
                </div>
                <Tabs value={mode} onValueChange={(v) => setMode(v as Mode)}>
                    <TabsList>
                        <TabsTrigger value="light">Light</TabsTrigger>
                        <TabsTrigger value="dark">Dark</TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>

            <div
                data-theme={mode}
                style={tokens as React.CSSProperties}
                className="space-y-6 rounded-lg border bg-background p-6 text-foreground"
            >
                <div className="flex flex-wrap gap-2">
                    <Button>Primary</Button>
                    <Button variant="secondary">Secondary</Button>
                    <Button variant="outline">Outline</Button>
                    <Button variant="ghost">Ghost</Button>
                    <Button variant="destructive">Destructive</Button>
                    <Button disabled>Disabled</Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Card title</CardTitle>
                        <CardDescription>
                            Surfaces use card, border and muted tokens.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="theme-preview-input">Email</Label>
                            <Input
                                id="theme-preview-input"
                                placeholder="you@example.com"
                                readOnly
                            />
                        </div>
                        <div className="flex items-center justify-between rounded-md border p-3">
                            <span className="text-sm">
                                Enable notifications
                            </span>
                            <Switch defaultChecked />
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge>Default</Badge>
                            <Badge variant="secondary">Secondary</Badge>
                            <Badge variant="outline">Outline</Badge>
                            <Badge variant="destructive">Destructive</Badge>
                        </div>
                    </CardContent>
                </Card>

                <div className="space-y-2">
                    <div className="rounded-md bg-muted p-3 text-sm text-muted-foreground">
                        Muted surface with muted-foreground text.
                    </div>
                    <div className="rounded-md bg-accent p-3 text-sm text-accent-foreground">
                        Accent surface with accent-foreground text.
                    </div>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead className="text-right">Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow>
                            <TableCell className="font-medium">
                                Ada Lovelace
                            </TableCell>
                            <TableCell>Developer</TableCell>
                            <TableCell className="text-right">
                                <Badge variant="secondary">Active</Badge>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell className="font-medium">
                                Alan Turing
                            </TableCell>
                            <TableCell>Admin</TableCell>
                            <TableCell className="text-right">
                                <Badge variant="outline">Invited</Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
