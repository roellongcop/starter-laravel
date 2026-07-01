import { useEffect, useLayoutEffect, useRef } from 'react';

// useLayoutEffect warns during SSR; fall back to useEffect on the server.
const useIsoLayoutEffect =
    typeof window !== 'undefined' ? useLayoutEffect : useEffect;

/**
 * Keeps a scroll container's position across Inertia navigations.
 *
 * Pages render the layout inline, so the whole layout — the sidebar included —
 * remounts on every visit and its scroll resets to the top. This restores the
 * last saved scrollTop on mount (before paint, so there's no flash) and
 * persists it as the user scrolls, keyed per container so multiple regions
 * don't clobber each other.
 */
export function usePersistentScroll<T extends HTMLElement>(key: string) {
    const ref = useRef<T>(null);

    useIsoLayoutEffect(() => {
        const el = ref.current;
        if (!el) {
            return;
        }

        const storageKey = `scroll:${key}`;
        const saved = sessionStorage.getItem(storageKey);
        if (saved !== null) {
            el.scrollTop = Number(saved);
        }

        const onScroll = () => {
            sessionStorage.setItem(storageKey, String(el.scrollTop));
        };

        el.addEventListener('scroll', onScroll, { passive: true });

        return () => {
            el.removeEventListener('scroll', onScroll);
        };
    }, [key]);

    return ref;
}
