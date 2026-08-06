<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dot.Tasks') }}</title>

        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@500;600;700;800&family=Karla:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles

        <style>
            :root {
                --paper: #faf6ec;
                --paper-deep: #f1e8d2;
                --ink: #241c0c;
                --ink-soft: #6b5d42;
                --mustard: #f1c62e;
                --amber: #f2a803;
                --amber-ink: #8a5800;
                --line: rgba(36, 28, 12, 0.12);
                --font-display: 'Schibsted Grotesk', system-ui, sans-serif;
                --font-body: 'Karla', system-ui, sans-serif;
                --font-mono: 'Space Mono', ui-monospace, monospace;
            }
            html { background: var(--paper); }
            body { background: var(--paper); }
            .font-display { font-family: var(--font-display); }
            .font-mono { font-family: var(--font-mono); }
        </style>
    </head>
    <body>
        <div class="font-sans text-[var(--ink)] antialiased" style="font-family: var(--font-body);">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
