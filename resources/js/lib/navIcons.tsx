import {
    Archive,
    BadgeCheck,
    Bell,
    Boxes,
    Building2,
    Circle,
    ClipboardList,
    Database,
    Download,
    ExternalLink,
    FileText,
    Files,
    FolderKanban,
    FolderOpen,
    Footprints,
    History,
    KeyRound,
    LayoutDashboard,
    Link as LinkIcon,
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
    UsersRound,
} from 'lucide-react';

/** Icon names selectable in the menu builder and rendered in the sidebar. */
export const NAV_ICONS: Record<string, LucideIcon> = {
    LayoutDashboard,
    ShieldCheck,
    Users,
    UsersRound,
    BadgeCheck,
    Tags,
    KeyRound,
    FolderOpen,
    FolderKanban,
    Files,
    Palette,
    Bell,
    Database,
    Download,
    Upload,
    Archive,
    Boxes,
    Building2,
    ClipboardList,
    FileText,
    Settings,
    Network,
    MonitorSmartphone,
    ScrollText,
    Footprints,
    History,
    Route: RouteIcon,
    ListChecks,
    Link: LinkIcon,
    ExternalLink,
};

export const iconNames = Object.keys(NAV_ICONS);

export function NavIcon({
    name,
    className,
}: {
    name?: string;
    className?: string;
}) {
    const Component = (name && NAV_ICONS[name]) || Circle;
    return <Component className={className} />;
}
