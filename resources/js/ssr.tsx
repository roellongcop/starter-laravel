import { ThemeProvider } from '@/Components/ThemeProvider';
import Toaster from '@/Components/Toaster';
import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { route } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Mirrors app.tsx's render tree for hydration. Omits ./bootstrap (its top-level
// `window.axios = …` would throw in Node). See docs/features/seo-and-ssr.md.
createServer((page) =>
    createInertiaApp({
        page,
        title: (title) => `${title} - ${appName}`,
        render: ReactDOMServer.renderToString,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.tsx`,
                import.meta.glob('./Pages/**/*.tsx'),
            ),
        setup: ({ App, props }) => {
            // route() has no @routes directive in Node; expose the shared Ziggy
            // config on globalThis for it to read.
            (globalThis as typeof globalThis & { Ziggy?: unknown }).Ziggy = (
                page.props as { ziggy?: unknown }
            ).ziggy;
            globalThis.route = route;

            const settings = page.props.settings as
                | { system?: { default_theme?: 'light' | 'dark' | 'system' } }
                | undefined;

            return (
                <ThemeProvider
                    defaultTheme={settings?.system?.default_theme ?? 'system'}
                >
                    <App {...props} />
                    <Toaster />
                </ThemeProvider>
            );
        },
    }),
);
