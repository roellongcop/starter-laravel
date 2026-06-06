import { router } from '@inertiajs/react';
import { useEffect } from 'react';

const IN_FLIGHT = ['Pending', 'Running'];

/**
 * Auto-reloads a single Inertia prop on an interval while any of the given row
 * statuses is still in-flight (Pending/Running), so sharded export/import jobs
 * surface live progress without the user clicking Refresh. Stops once every row
 * has settled.
 */
export function useStatusPoll(
    statuses: string[],
    onlyKey: string,
    intervalMs = 2500,
): void {
    const active = statuses.some((s) => IN_FLIGHT.includes(s));

    useEffect(() => {
        if (!active) {
            return;
        }

        const id = window.setInterval(
            () => router.reload({ only: [onlyKey] }),
            intervalMs,
        );

        return () => window.clearInterval(id);
    }, [active, onlyKey, intervalMs]);
}
