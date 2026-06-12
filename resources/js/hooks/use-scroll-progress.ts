import { useEffect, useRef, useState } from 'react';

/**
 * Tracks whole-page scroll as a 0→1 progress value, throttled to one update
 * per animation frame (passive listener, transform-only consumers). Returns a
 * frozen `0` when the user prefers reduced motion so scroll-driven effects stay
 * static — the skill flags scroll-jacking as a motion-sickness risk.
 */
export function useScrollProgress(): number {
    const reduced =
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const [progress, setProgress] = useState(0);
    const frame = useRef<number | null>(null);

    useEffect(() => {
        if (reduced) {
            return;
        }

        const compute = () => {
            frame.current = null;
            const max =
                document.documentElement.scrollHeight - window.innerHeight;
            setProgress(max > 0 ? Math.min(1, window.scrollY / max) : 0);
        };

        const onScroll = () => {
            if (frame.current === null) {
                frame.current = window.requestAnimationFrame(compute);
            }
        };

        compute();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        return () => {
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onScroll);
            if (frame.current !== null) {
                window.cancelAnimationFrame(frame.current);
            }
        };
    }, [reduced]);

    return progress;
}
