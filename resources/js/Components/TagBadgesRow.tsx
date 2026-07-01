import {
    type CSSProperties,
    type ReactNode,
    useEffect,
    useLayoutEffect,
    useRef,
    useState,
} from 'react';

import { Badge } from '@/Components/ui/badge';
import { cn } from '@/lib/utils';
import { type TagChip } from '@/types';

// useLayoutEffect warns during SSR; fall back to useEffect on the server.
const useIsoLayoutEffect =
    typeof window !== 'undefined' ? useLayoutEffect : useEffect;

const GAP = 6; // matches gap-1.5 (0.375rem)
const PLUS_RESERVE = 44; // space kept for the "+N" chip when overflowing

function Chip({ tag }: { tag: TagChip }) {
    return (
        <Badge
            variant="outline"
            className="max-w-[10rem] shrink-0 gap-1.5 font-normal"
        >
            <span
                className="h-2 w-2 shrink-0 rounded-full"
                style={{ backgroundColor: tag.color }}
                aria-hidden
            />
            <span className="truncate">{tag.name}</span>
        </Badge>
    );
}

/**
 * A SINGLE-ROW tag list with overflow handling: shows as many whole chips as fit
 * (each truncated with … if a name is long) and a "+N" chip for the remainder.
 * Always reserves one chip-row of height — even with zero tags — so cards keep a
 * consistent height. Recomputes on container resize. An optional `action`
 * (e.g. an edit button) renders immediately after the chips and is reserved in
 * the fit calc, so it hugs the tags instead of being pushed to the far edge.
 * Compare to <TagBadges>, which wraps to multiple lines.
 */
export default function TagBadgesRow({
    tags,
    className,
    action,
}: {
    tags: TagChip[];
    className?: string;
    action?: ReactNode;
}) {
    const containerRef = useRef<HTMLDivElement>(null);
    const measureRef = useRef<HTMLDivElement>(null);
    const actionRef = useRef<HTMLDivElement>(null);
    const [visible, setVisible] = useState(tags.length);

    useIsoLayoutEffect(() => {
        const container = containerRef.current;
        const measure = measureRef.current;
        if (!container || !measure) {
            return;
        }

        const recalc = () => {
            // Reserve room for the trailing action so chips never overlap it.
            const actionWidth = actionRef.current?.offsetWidth ?? 0;
            const max =
                container.clientWidth - (actionWidth ? actionWidth + GAP : 0);
            const items = Array.from(measure.children) as HTMLElement[];
            const count = items.length;
            if (count === 0) {
                setVisible(0);

                return;
            }

            const total = items.reduce(
                (sum, item, i) => sum + item.offsetWidth + (i > 0 ? GAP : 0),
                0,
            );
            if (total <= max) {
                setVisible(count);

                return;
            }

            let used = 0;
            let fit = 0;
            for (let i = 0; i < count; i++) {
                const width = items[i].offsetWidth + (fit > 0 ? GAP : 0);
                if (used + width + PLUS_RESERVE <= max) {
                    used += width;
                    fit++;
                } else {
                    break;
                }
            }
            setVisible(Math.max(fit, 1));
        };

        recalc();
        const observer = new ResizeObserver(recalc);
        observer.observe(container);

        return () => observer.disconnect();
    }, [tags]);

    const hidden = tags.length - visible;
    const measureStyle: CSSProperties = {
        position: 'absolute',
        top: 0,
        left: 0,
        visibility: 'hidden',
    };

    return (
        <div
            ref={containerRef}
            className={cn(
                'relative flex h-6 items-center gap-1.5 overflow-hidden',
                className,
            )}
        >
            {/* Off-flow copy of the full set, used only to measure widths. */}
            <div
                ref={measureRef}
                style={measureStyle}
                className="flex gap-1.5"
                aria-hidden
            >
                {tags.map((tag) => (
                    <Chip key={tag.token} tag={tag} />
                ))}
            </div>

            <div className="flex gap-1.5 overflow-hidden">
                {tags.slice(0, visible).map((tag) => (
                    <Chip key={tag.token} tag={tag} />
                ))}
                {hidden > 0 && (
                    <Badge
                        variant="secondary"
                        className="shrink-0 font-normal"
                        title={tags
                            .slice(visible)
                            .map((t) => t.name)
                            .join(', ')}
                    >
                        +{hidden}
                    </Badge>
                )}
            </div>

            {action && (
                <div ref={actionRef} className="shrink-0">
                    {action}
                </div>
            )}
        </div>
    );
}
