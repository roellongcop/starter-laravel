// Auto-logout after a period of inactivity, driven by the `auto_logout_seconds`
// SystemSetting (0 = off) shared via Inertia. Side-effect only — mount it once in
// AuthenticatedLayout so it runs only on authenticated pages.
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

import { toast } from '@/hooks/use-toast';

// Resetting on every event would be wasteful (mousemove fires constantly), so
// reschedules are throttled to at most once per second.
const ACTIVITY_EVENTS = [
    'mousemove',
    'mousedown',
    'keydown',
    'scroll',
    'touchstart',
    'focus',
] as const;

const RESET_THROTTLE_MS = 1000;

export function useIdleLogout(): void {
    const seconds = usePage().props.settings.system.auto_logout_seconds;
    // Guards against a double POST if activity races the firing logout timer.
    const loggingOut = useRef(false);

    useEffect(() => {
        if (!seconds || seconds <= 0) return;

        // Warn the user shortly before the cutoff (capped at 30s, and never
        // longer than half the window so tiny timeouts still warn sensibly).
        const warnWindow = Math.min(30, Math.floor(seconds / 2));
        let warnTimer: ReturnType<typeof setTimeout> | undefined;
        let logoutTimer: ReturnType<typeof setTimeout> | undefined;
        let lastReset = 0;

        const clearTimers = (): void => {
            if (warnTimer) clearTimeout(warnTimer);
            if (logoutTimer) clearTimeout(logoutTimer);
        };

        const schedule = (): void => {
            clearTimers();

            if (warnWindow > 0) {
                warnTimer = setTimeout(
                    () =>
                        toast({
                            variant: 'destructive',
                            description: `You will be logged out in ${warnWindow}s due to inactivity.`,
                        }),
                    (seconds - warnWindow) * 1000,
                );
            }

            logoutTimer = setTimeout(() => {
                if (loggingOut.current) return;
                loggingOut.current = true;
                router.post(route('logout'));
            }, seconds * 1000);
        };

        const onActivity = (): void => {
            const now = Date.now();
            if (now - lastReset < RESET_THROTTLE_MS) return;
            lastReset = now;
            schedule();
        };

        schedule();
        ACTIVITY_EVENTS.forEach((event) =>
            window.addEventListener(event, onActivity, { passive: true }),
        );

        return () => {
            clearTimers();
            ACTIVITY_EVENTS.forEach((event) =>
                window.removeEventListener(event, onActivity),
            );
        };
    }, [seconds]);
}
