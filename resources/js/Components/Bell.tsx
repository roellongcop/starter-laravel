import { Link, usePage } from '@inertiajs/react';
import { Bell as BellIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { type PageProps } from '@/types';

export default function Bell() {
    const { bell } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const count = bell?.unread_count ?? 0;
    const recent = bell?.recent ?? [];

    // Close on click/tap outside or Escape. A backdrop div can't be used here
    // because the parent nav's `backdrop-blur` makes `fixed` positioning
    // resolve against the nav, not the viewport.
    useEffect(() => {
        if (!open) {
            return;
        }

        function handlePointerDown(event: MouseEvent) {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        }

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('mousedown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [open]);

    return (
        <div ref={containerRef} className="relative">
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                className="relative rounded-full p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                title="Notifications"
                aria-label="Notifications"
            >
                <BellIcon className="h-5 w-5" />
                {count > 0 && (
                    <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-semibold text-destructive-foreground">
                        {count > 9 ? '9+' : count}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 z-50 mt-2 w-80 rounded-md border bg-popover text-popover-foreground shadow-lg">
                    <div className="border-b px-4 py-2 text-sm font-semibold">
                        Notifications
                    </div>
                    <div className="max-h-80 overflow-auto">
                        {recent.length === 0 && (
                            <p className="px-4 py-6 text-center text-sm text-muted-foreground">
                                Nothing yet.
                            </p>
                        )}
                        {recent.map((n) => (
                            <Link
                                key={n.id}
                                href={n.link ?? route('notifications.index')}
                                onClick={() => setOpen(false)}
                                className={`block border-b px-4 py-2 text-sm last:border-0 hover:bg-accent ${
                                    n.read
                                        ? 'text-muted-foreground'
                                        : 'font-medium'
                                }`}
                            >
                                {n.message}
                            </Link>
                        ))}
                    </div>
                    <Link
                        href={route('notifications.index')}
                        onClick={() => setOpen(false)}
                        className="block border-t px-4 py-2 text-center text-sm text-primary hover:underline"
                    >
                        View all
                    </Link>
                </div>
            )}
        </div>
    );
}
