<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="guest-body font-sans antialiased text-slate-100">
        <div class="relative min-h-screen overflow-hidden">
            <div class="texture-grid absolute inset-0 opacity-30"></div>
            <div class="glow-ring absolute -left-24 top-24 h-80 w-80 blur-3xl"></div>
            <div class="glow-ring absolute -right-16 bottom-16 h-96 w-96 blur-3xl"></div>

            <div class="relative flex min-h-screen flex-col items-center justify-center px-6 py-12 sm:px-8 lg:px-16">
                <div class="mb-10 flex flex-col items-center text-center">
                    <a href="/" class="group flex items-center gap-3 text-lg font-semibold text-slate-100">
                        <img
                            src="{{ asset('images/osaka.png') }}"
                            alt="GSP logo"
                            class="h-16 w-auto drop-shadow-md transition group-hover:scale-105"
                        >
                        <span>{{ config('app.name', 'GSP Console') }}</span>
                    </a>
                </div>

                <div class="glass-panel w-full max-w-6xl rounded-3xl border border-white/10 shadow-2xl">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
