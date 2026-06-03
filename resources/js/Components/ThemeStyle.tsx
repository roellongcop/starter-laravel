import { usePage } from '@inertiajs/react';

import { type PageProps } from '@/types';

/**
 * Injects the active theme's tokens as CSS variables onto :root (light) and
 * [data-theme="dark"] (dark), overriding the static defaults in app.css. This is
 * how a Theme's `tokens` JSON restyles the whole app.
 */
export default function ThemeStyle() {
    const { theme } = usePage<PageProps>().props;

    if (!theme) return null;

    const block = (selector: string, vars: Record<string, string>) => {
        const body = Object.entries(vars)
            .map(([k, v]) => `${k}: ${v};`)
            .join(' ');
        return body ? `${selector} { ${body} }` : '';
    };

    const css = [
        block(':root', theme.light ?? {}),
        block('[data-theme="dark"]', theme.dark ?? {}),
    ]
        .filter(Boolean)
        .join('\n');

    return <style data-theme-tokens>{css}</style>;
}
