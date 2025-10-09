<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name', 'Travian T4.6'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600;jetbrains-mono:400&display=swap" rel="stylesheet" />

    @livewireStyles
    @fluxAppearance

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="font-sans text-slate-100 bg-slate-950 min-h-screen">
    <div class="min-h-screen flex flex-col" id="app">
        @yield('content')
    </div>

    @fluxScripts
    @stack('scripts')
</body>
</html>
