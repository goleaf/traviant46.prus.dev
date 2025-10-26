<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name', 'Travian T4.6'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600;jetbrains-mono:400&display=swap" rel="stylesheet" />

    @include('layouts.partials.vite-assets')

    @stack('head')
</head>
<body class="@yield('body_class', 'min-h-screen bg-slate-950/95 text-white')">
    <div class="@yield('app_container_class', 'flex min-h-screen items-center justify-center px-4 py-12')">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
