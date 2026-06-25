import { Link } from '@inertiajs/react';

import Can from '@/Components/Can';
import { cn } from '@/lib/utils';

type TabKey = 'teams' | 'categories' | 'people';

const TABS: { key: TabKey; label: string; ability: string; route: string }[] = [
    {
        key: 'teams',
        label: 'Teams',
        ability: 'teams.index',
        route: 'teams.index',
    },
    {
        key: 'categories',
        label: 'Team Categories',
        ability: 'team-categories.index',
        route: 'team-categories.index',
    },
    {
        key: 'people',
        label: 'People',
        ability: 'people.index',
        route: 'people.index',
    },
];

/**
 * Route-based tab bar for the "Teams & People" page. Each tab is an Inertia
 * <Link> to its own index route (so each tab keeps its own filters/pagination),
 * styled to match the shadcn Tabs primitive and gated per ability.
 */
export default function TeamsPeopleTabs({ current }: { current: TabKey }) {
    return (
        <div className="mb-4 inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground">
            {TABS.map((tab) => (
                <Can key={tab.key} ability={tab.ability}>
                    <Link
                        href={route(tab.route)}
                        aria-current={current === tab.key ? 'page' : undefined}
                        className={cn(
                            'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                            current === tab.key
                                ? 'bg-background text-foreground shadow-sm'
                                : 'hover:text-foreground',
                        )}
                    >
                        {tab.label}
                    </Link>
                </Can>
            ))}
        </div>
    );
}
