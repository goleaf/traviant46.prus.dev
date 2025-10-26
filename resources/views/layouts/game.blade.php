<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php($cspNonce = app()->has('csp.nonce') ? app('csp.nonce') : null)

    <title>@yield('title', __('Game Console') . ' • ' . config('app.name', 'Travian T4.6'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600&display=swap" rel="stylesheet" />

    @livewireStyles(['nonce' => $cspNonce])
    @livewireScriptConfig(['nonce' => $cspNonce])

    @include('layouts.partials.vite-assets', ['cspNonce' => $cspNonce])

    @stack('head')
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 font-mono">
    <main class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-12">
        {{ $slot }}
    </main>

    @livewireScripts(['nonce' => $cspNonce])
    @stack('scripts')
</body>
</html>
