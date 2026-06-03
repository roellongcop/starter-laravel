import { router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { Button } from '@/Components/ui/button';
import { previousUrl } from '@/lib/navHistory';

interface Props {
    /** Where to go when there's no prior in-app page (direct load / refresh). */
    fallback?: string;
    label?: string;
    className?: string;
}

/**
 * Returns to the previous page the user visited with a FRESH server fetch
 * (router.get) rather than window.history.back(), which Inertia restores from
 * its history cache (stale data). Falls back to `fallback` on a direct load.
 */
export default function BackButton({
    fallback = '/',
    label = 'Back',
    className,
}: Props) {
    const goBack = () => {
        // Fresh GET to the previous page (or fallback on a direct load). The
        // navHistory `navigate` handler pops the stack when it sees this visit.
        router.get(previousUrl() ?? fallback);
    };

    return (
        <Button variant="outline" onClick={goBack} className={className}>
            <ArrowLeft className="h-4 w-4" />
            {label}
        </Button>
    );
}
