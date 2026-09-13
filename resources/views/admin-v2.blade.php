<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Whistle-Works') }} - Executive Admin</title>

    {{-- Favicon --}}
    <link rel="shortcut icon" type="image/png" href="{{ asset(function_exists('settings') && settings()?->favicon ? settings()->favicon : 'default/favicon.png') }}" />

    {{-- Google Fonts: Inter & Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    {{-- Scripts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/Admin/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-[#0B0F17] text-slate-100 selection:bg-indigo-500 selection:text-white min-h-screen">
    @inertia
</body>
</html>
