import { useEffect, useRef, useState } from 'react';

/**
 * Reveal-on-scroll: returns a ref to attach to a section and a `visible` flag that
 * flips true the first time the element scrolls into view. Respects
 * prefers-reduced-motion by starting visible (no animation). Dependency-free.
 */
export function useReveal<T extends HTMLElement = HTMLDivElement>(): {
    ref: React.RefObject<T>;
    visible: boolean;
} {
    const ref = useRef<T>(null);
    // Start hidden so SSR markup hydrates without a mismatch; the effect reveals.
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const reduced = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;
        if (reduced) {
            setVisible(true);
            return;
        }
        if (visible) return;
        const el = ref.current;
        if (!el) return;

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((e) => e.isIntersecting)) {
                    setVisible(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.15, rootMargin: '0px 0px -10% 0px' },
        );

        observer.observe(el);
        return () => observer.disconnect();
    }, [visible]);

    return { ref, visible };
}
