<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Early inline appearance script: read localStorage, cookie, or server value and apply theme immediately. --}}
        <script>
            (function() {
                const serverAppearance = '{{ $appearance ?? "system" }}';

                function readLocalStorage() {
                    try {
                        return localStorage.getItem('appearance');
                    } catch (e) {
                        return null;
                    }
                }

                function readCookie() {
                    try {
                        const m = document.cookie.match('(?:^|; )appearance=([^;]+)');
                        return m ? decodeURIComponent(m[1]) : null;
                    } catch (e) {
                        return null;
                    }
                }

                let appearance = readLocalStorage() || readCookie() || serverAppearance || 'system';

                if (appearance === 'system') {
                    try {
                        appearance = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    } catch (e) {
                        appearance = 'light';
                    }
                }

                if (appearance === 'dark') {
                    document.documentElement.classList.add('dark');
                    // core theme variables
                    document.documentElement.style.setProperty('--background', '#0a0a0a');
                    document.documentElement.style.setProperty('--foreground', '#fafafa');
                    document.documentElement.style.setProperty('--sidebar-background', '#121212');
                    // Tailwind / utility fallbacks (so `bg-white` and similar follow theme immediately)
                    document.documentElement.style.setProperty('--color-white', '#0a0a0a');
                    document.documentElement.style.setProperty('--color-card', '#0a0a0a');
                    document.documentElement.style.setProperty('--color-popover', '#0a0a0a');
                    document.documentElement.style.setProperty('--color-card-foreground', '#fafafa');
                    document.documentElement.style.setProperty('--color-popover-foreground', '#fafafa');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.style.setProperty('--background', '#ffffff');
                    document.documentElement.style.setProperty('--foreground', '#0a0a0a');
                    document.documentElement.style.setProperty('--sidebar-background', '#fafafa');
                    document.documentElement.style.setProperty('--color-white', '#ffffff');
                    document.documentElement.style.setProperty('--color-card', '#ffffff');
                    document.documentElement.style.setProperty('--color-popover', '#ffffff');
                    document.documentElement.style.setProperty('--color-card-foreground', '#0a0a0a');
                    document.documentElement.style.setProperty('--color-popover-foreground', '#0a0a0a');
                }
            })();
        </script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
