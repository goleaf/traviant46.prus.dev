<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php($cspNonce = app()->has('csp.nonce') ? app('csp.nonce') : null)

    <title>@yield('title', config('app.name', 'Travian T4.6'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600;jetbrains-mono:400&display=swap" rel="stylesheet" />

    @livewireStyles(['nonce' => $cspNonce])
    @livewireScriptConfig(['nonce' => $cspNonce])
    @fluxAppearance(['nonce' => $cspNonce])

    @include('layouts.partials.vite-assets', ['cspNonce' => $cspNonce])

    @stack('head')
</head>
<body class="@yield('body_class', 'app-body')">
    <div class="@yield('app_container_class', 'app-shell')" id="app">
        @yield('content')
    </div>

    @livewireScripts(['nonce' => $cspNonce])
    @fluxScripts(['nonce' => $cspNonce])
    @stack('scripts')
</body>
</html>
