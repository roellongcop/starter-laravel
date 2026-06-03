import { Link, usePage } from '@inertiajs/react';
import {
    Archive,
    Bell,
    Circle,
    Database,
    Download,
    Files,
    FolderOpen,
    Footprints,
    KeyRound,
    LayoutDashboard,
    ListChecks,
    type LucideIcon,
    MonitorSmartphone,
    Network,
    Palette,
    Route as RouteIcon,
    ScrollText,
    Settings,
    ShieldCheck,
    Tags,
    Upload,
    Users,
} from 'lucide-react';

import { cn } from '@/lib/utils';
import { type NavItem, type PageProps } from '@/types';

const ICONS: Record<string, LucideIcon> = {
    LayoutDashboard,
    ShieldCheck,
    Users,
    Tags,
    KeyRound,
    FolderOpen,
    Files,
    Palette,
    Bell,
    Database,
    Download,
    Upload,
    Archive,
    Settings,
    Network,
    MonitorSmartphone,
    ScrollText,
    Footprints,
    Route: RouteIcon,
    ListChecks,
};

function Icon({ name, className }: { name?: string; className?: string }) {
    const Component = (name && ICONS[name]) || Circle;
    return <Component className={className} />;
}

function NavLeaf({ item }: { item: NavItem }) {
    const active =
        item.href != null &&
        typeof window !== 'undefined' &&
        window.location.pathname.startsWith(item.href);

    return (
        <Link
            href={item.href ?? '#'}
            className={cn(
                'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                active
                    ? 'bg-accent text-accent-foreground'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
            )}
        >
            <Icon name={item.icon} className="h-4 w-4 shrink-0" />
            <span>{item.label}</span>
        </Link>
    );
}

export default function Sidebar({ className }: { className?: string }) {
    const { navigation } = usePage<PageProps>().props;

    return (
        <nav className={cn('flex flex-col gap-4', className)}>
            {navigation.map((item, i) =>
                item.children ? (
                    <div key={item.label + i} className="flex flex-col gap-1">
                        <p className="px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground/70">
                            {item.label}
                        </p>
                        {item.children.map((child) => (
                            <NavLeaf
                                key={child.key ?? child.label}
                                item={child}
                            />
                        ))}
                    </div>
                ) : (
                    <NavLeaf key={item.key ?? item.label} item={item} />
                ),
            )}
        </nav>
    );
}
