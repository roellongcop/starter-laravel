import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

import { type Crumb } from '@/types';

/**
 * Wayfinding trail shown above a page title. The last crumb is the current
 * page (no href); earlier crumbs link to their ancestors.
 */
export default function Breadcrumbs({
    trail,
    className,
}: {
    trail: Crumb[];
    className?: string;
}) {
    return (
        <nav aria-label="Breadcrumb" className={className}>
            <ol className="flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground">
                {trail.map((crumb, i) => {
                    const isLast = i === trail.length - 1;
                    return (
                        <li key={i} className="flex items-center gap-1.5">
                            {crumb.href && !isLast ? (
                                <Link
                                    href={crumb.href}
                                    className="transition-colors hover:text-foreground"
                                >
                                    {crumb.label}
                                </Link>
                            ) : (
                                <span
                                    className="font-medium text-foreground"
                                    aria-current={isLast ? 'page' : undefined}
                                >
                                    {crumb.label}
                                </span>
                            )}
                            {!isLast && (
                                <ChevronRight
                                    className="h-4 w-4 shrink-0"
                                    aria-hidden
                                />
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
