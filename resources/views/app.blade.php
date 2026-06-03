<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

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

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

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
