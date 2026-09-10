<!doctype html>
<html lang="en" dir="ltr">
<head>
    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="{!! strip_tags(settings()->description ?? '') !!}">
    <meta name="author" content="{{ settings()->author ?? '' }}">
    <meta name="keywords" content="{!! strip_tags(settings()->keywords ?? '') !!}">

    <!-- FAVICON -->
    <link rel="shortcut icon" type="image/png" href="{{ asset(settings()->favicon ?? 'default/favicon.png') }}" />

    <!-- TITLE -->
    <title>{{ config('app.name') }} - {{ $title ?? settings()->title ?? '' }}</title>
    <!-- Scripts -->

    <script>

    window.authUserId = {{ auth()->id() ?? 'null' }};
</script>

    {{-- Google Fonts: preconnect + non-blocking load --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>

    @vite(['resources/js/app.js'])

    @include('backend.partials._styles')

    @livewireStyles


</head>

<body class="ltr app sidebar-mini">
    @include('backend.partials._loader')

    <!-- PAGE -->
    <div class="page">
        <div class="page-main">
            @include('backend.partials._header')
            @include('backend.partials._sidebar')

            @yield('content')
        </div>

        @include('backend.partials._footer')
    </div>
    <!-- page -->
    @include('backend.partials._scripts')

    @livewireScripts
</body>

</html>
