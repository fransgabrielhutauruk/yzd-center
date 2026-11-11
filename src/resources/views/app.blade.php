<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Favicon & PWA Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">
    <meta name="theme-color" content="#7c3aed">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="YZD Center">

    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Tangkap event PWA beforeinstallprompt sedini mungkin sebelum Vue di-mount
        window.__deferredPwaPrompt = null;
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            window.__deferredPwaPrompt = e;
            window.dispatchEvent(new CustomEvent('pwa-prompt-available', { detail: e }));
        });
        window.addEventListener('appinstalled', function () {
            window.__deferredPwaPrompt = null;
            window.dispatchEvent(new CustomEvent('pwa-installed'));
        });

        // Registrasi Service Worker ringan (pass-through tanpa offline cache)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            });
        }
    </script>
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="h-full min-h-screen min-h-dvh font-sans antialiased bg-slate-50 text-foreground dark:bg-slate-950 transition-colors duration-200">
    @inertia
</body>
</html>

