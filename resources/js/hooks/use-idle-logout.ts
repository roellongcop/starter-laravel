// Auto-logout after inactivity, driven by the `auto_logout_seconds` SystemSetting
// (0 = off). Side-effect only — safe to mount on any page (it no-ops for guests
// and when the timeout is off). The server-side EnforceIdleTimeout middleware is
// the real boundary; this hook adds the warning toast + proactive redirect and
// heartbeats the server so an active-but-not-navigating session stays alive.
// See docs/features/notifications-sessions-audit.md.
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
    const page = usePage();
    const seconds = page.props.settings.system.auto_logout_seconds;
    // Null at runtime for guests (despite the non-null type); the hook no-ops
    // for them so it's safe to mount on public pages like `/` and `/contact`.
    const userToken = page.props.auth.user?.token;
    // Guards against a double POST if activity races the firing logout timer.
    const loggingOut = useRef(false);

    useEffect(() => {
        if (!seconds || seconds <= 0 || !userToken) return;

        // Warn the user shortly before the cutoff (capped at 30s, and never
        // longer than half the window so tiny timeouts still warn sensibly).
        const warnWindow = Math.min(30, Math.floor(seconds / 2));
        // Bump the server's idle clock on activity, throttled well within the
        // window so an actively-used page isn't logged out server-side.
        const heartbeatThrottleMs =
            Math.max(15, Math.floor(seconds / 3)) * 1000;
        let warnTimer: ReturnType<typeof setTimeout> | undefined;
        let logoutTimer: ReturnType<typeof setTimeout> | undefined;
        let lastReset = 0;
        let lastHeartbeat = 0;

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

            if (now - lastHeartbeat >= heartbeatThrottleMs) {
                lastHeartbeat = now;
                window.axios.post(route('session.heartbeat')).catch(() => {});
            }

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
    }, [seconds, userToken]);
}
