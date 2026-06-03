import { Link, usePage } from '@inertiajs/react';

import { NavIcon } from '@/lib/navIcons';
import { cn } from '@/lib/utils';
import { type NavItem, type PageProps } from '@/types';

function NavLeaf({ item }: { item: NavItem }) {
    const active =
        !item.external &&
        item.href != null &&
        typeof window !== 'undefined' &&
        window.location.pathname.startsWith(item.href);

    const className = cn(
        'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
        active
            ? 'bg-accent text-accent-foreground'
            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
    );

    const inner = (
        <>
            <NavIcon name={item.icon} className="h-4 w-4 shrink-0" />
            <span>{item.label}</span>
        </>
    );

    // External links open in a new tab; internal links use Inertia navigation.
    if (item.external) {
        return (
            <a
                href={item.href ?? '#'}
                target="_blank"
                rel="noopener noreferrer"
                className={className}
            >
                {inner}
            </a>
        );
    }

    return (
        <Link href={item.href ?? '#'} className={className}>
            {inner}
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
