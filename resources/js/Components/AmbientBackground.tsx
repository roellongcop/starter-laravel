import { useEffect, useRef } from 'react';

import YinYang from '@/Components/YinYang';

/** Tiling fractal-noise grain, inlined so it costs no extra request. */
const GRAIN =
    "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E\")";

/**
 * Decorative, non-interactive backdrop for the landing page — built for an
 * "alive and mysterious" mood:
 * - a torchlight that follows the cursor, revealing the page out of the dark;
 * - two slow-drifting "fog" blobs and restless film grain for living texture;
 * - a large faint yin/yang that travels bottom→top as `progress` (0→1 page
 *   scroll) advances and rotates with it.
 * Pure transform/opacity. Animations self-disable under `prefers-reduced-motion`;
 * the cursor torch is pointer-driven (not autoplay) so it stays.
 */
export default function AmbientBackground({ progress }: { progress: number }) {
    const spotRef = useRef<HTMLDivElement>(null);

    // Move a soft radial glow to the pointer (mouse/pen only), rAF-throttled.
    useEffect(() => {
        const el = spotRef.current;
        if (!el) {
            return;
        }
        let raf = 0;
        let x = 0;
        let y = 0;
        const apply = () => {
            raf = 0;
            el.style.opacity = '1';
            el.style.transform = `translate3d(${x - 320}px, ${y - 320}px, 0)`;
        };
        const onMove = (e: PointerEvent) => {
            if (e.pointerType === 'touch') {
                return;
            }
            x = e.clientX;
            y = e.clientY;
            if (!raf) {
                raf = window.requestAnimationFrame(apply);
            }
        };
        window.addEventListener('pointermove', onMove, { passive: true });
        return () => {
            window.removeEventListener('pointermove', onMove);
            if (raf) {
                window.cancelAnimationFrame(raf);
            }
        };
    }, []);

    // Travel from just below the fold up past the top as the page scrolls.
    const travelY = 42 - progress * 92; // vh: +42 (bottom) → -50 (top)
    const rotate = progress * 300;

    return (
        <div
            aria-hidden
            className="pointer-events-none fixed inset-0 -z-10 overflow-hidden"
        >
            {/* Restless film grain */}
            <div
                className="absolute inset-[-24px] animate-grain opacity-[0.04] motion-reduce:animate-none"
                style={{ backgroundImage: GRAIN }}
            />

            {/* Drifting "fog" glow blobs */}
            <div className="absolute -left-32 top-[-10%] h-[34rem] w-[34rem] animate-float-slow rounded-full bg-foreground/[0.05] blur-3xl motion-reduce:animate-none dark:bg-foreground/[0.07]" />
            <div className="absolute -right-40 top-1/3 h-[40rem] w-[40rem] animate-float-slower rounded-full bg-foreground/[0.04] blur-3xl motion-reduce:animate-none dark:bg-foreground/[0.06]" />

            {/* Scroll-driven traveling mark */}
            <div
                className="absolute left-1/2 top-1/2 opacity-[0.04] blur-[1px] will-change-transform dark:opacity-[0.06]"
                style={{
                    transform: `translate3d(-50%, ${travelY}vh, 0) rotate(${rotate}deg)`,
                }}
            >
                <YinYang className="h-[28rem] w-[28rem] sm:h-[36rem] sm:w-[36rem]" />
            </div>

            {/* Cursor torchlight */}
            <div
                ref={spotRef}
                className="absolute left-0 top-0 h-[640px] w-[640px] rounded-full opacity-0 blur-2xl transition-opacity duration-1000 will-change-transform"
                style={{
                    background:
                        'radial-gradient(closest-side, hsl(var(--foreground) / 0.1), transparent)',
                }}
            />
        </div>
    );
}
