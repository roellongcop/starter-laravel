import '../css/app.css';
import './bootstrap';

import { ThemeProvider } from '@/Components/ThemeProvider';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

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
        };

        root.render(
            <ThemeProvider
                defaultTheme={initial.settings?.default_theme ?? 'system'}
            >
                <App {...props} />
            </ThemeProvider>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
