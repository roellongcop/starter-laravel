<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- No static <title>: it's owned by <Head>/<Seo> via @inertiaHead, so
             SSR pages don't get a duplicate. See docs/features/seo-and-ssr.md. --}}

        <!-- Apply persisted theme before paint to avoid a flash of the wrong theme -->
        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('keen-admin-theme');
                    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    var resolved = stored === 'dark' || stored === 'light'
                        ? stored
                        : (prefersDark ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-theme', resolved);
                } catch (e) {}
            })();
        </script>

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
