import { useState } from 'react';

import { cn } from '@/lib/utils';

interface Props {
    name: string;
    src?: string | null;
    /** Pixel size of the (square) avatar. */
    size?: number;
    className?: string;
}

/** Derive up to two initials from a display name. */
function initials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return '?';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

/**
 * Append a preset width to an internal image URL so each render requests an
 * appropriately small, server-cached size (retina-aware) instead of the full
 * image. External URLs (http…) are left untouched.
 */
function sized(src: string, size: number): string {
    const internal = src.includes('/media/') || src.includes('/avatar/');
    // Leave external URLs and URLs that already specify a width untouched.
    if (!internal || /[?&]w=/.test(src)) {
        return src;
    }
    const dpr =
        typeof window !== 'undefined'
            ? Math.min(window.devicePixelRatio || 1, 2)
            : 1;
    const w = Math.round(size * dpr);
    return `${src}${src.includes('?') ? '&' : '?'}w=${w}`;
}

/**
 * Circular user avatar with an initials fallback; sizes internal image URLs
 * retina-aware (see sized()). See docs/features/files-and-media.md.
 */
export default function Avatar({ name, src, size = 36, className }: Props) {
    const [failed, setFailed] = useState(false);
    const dimension = { width: size, height: size };
    const resolved = src ? sized(src, size) : null;

    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-muted text-muted-foreground',
                className,
            )}
            style={dimension}
        >
            {resolved && !failed ? (
                <img
                    src={resolved}
                    alt={name}
                    className="h-full w-full object-cover"
                    onError={() => setFailed(true)}
                />
            ) : (
                <span
                    className="font-medium"
                    style={{ fontSize: Math.max(11, Math.round(size * 0.4)) }}
                >
                    {initials(name)}
                </span>
            )}
        </span>
    );
}
