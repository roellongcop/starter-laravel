import '../css/app.css';
import './bootstrap';

import { ThemeProvider } from '@/Components/ThemeProvider';
import Toaster from '@/Components/Toaster';
import { toast } from '@/hooks/use-toast';
import { initNavHistory } from '@/lib/navHistory';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

interface Flash {
    success?: string | null;
    error?: string | null;
}

/** Turn a request's flash bag into toasts (fires once per Laravel one-shot flash). */
function showFlash(flash?: Flash): void {
    if (flash?.success)
        toast({ variant: 'success', description: flash.success });
    if (flash?.error)
        toast({ variant: 'destructive', description: flash.error });
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        const initial = props.initialPage.props as {
            settings?: { default_theme?: 'light' | 'dark' | 'system' };
            flash?: Flash;
        };

        // Track in-app navigation so <BackButton> can revisit the previous page
        // with a fresh request instead of a stale history-cache restore.
        initNavHistory();

        // Flash on first load (e.g. landing after a post-login redirect)…
        showFlash(initial.flash);
        // …and on every subsequent Inertia visit (POST→redirect→GET, etc.).
        router.on('success', (event) =>
            showFlash((event.detail.page.props as { flash?: Flash }).flash),
        );

        root.render(
            <ThemeProvider
                defaultTheme={initial.settings?.default_theme ?? 'system'}
            >
                <App {...props} />
                <Toaster />
            </ThemeProvider>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
