<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Whistle-Works') }} - Executive Admin</title>

    {{-- Favicon --}}
    <link rel="shortcut icon" type="image/png" href="{{ asset(function_exists('settings') && settings()?->favicon ? settings()->favicon : 'default/favicon.png') }}" />

    {{-- Google Fonts: Inter & JetBrains Mono (Industry Standard for Clean Executive UI) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Theme Initializer (Prevents FOUC) --}}
    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>

    {{-- Scripts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/Admin/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-slate-100/70 text-slate-900 dark:bg-[#1E1E2C] dark:text-slate-100 selection:bg-[#F29F67]/30 selection:text-[#F29F67] min-h-screen transition-colors duration-200">
    @inertia
</body>
</html>
