<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @hasSection('title')
        <title>@yield('title') — {{ config('app.name', 'Travian T4.6') }}</title>
    @else
        <title>{{ config('app.name', 'Travian T4.6') }}</title>
    @endif

    @yield('meta')

    {{-- Preserve existing asset pipeline: individual screens can push their styles/scripts. --}}
    @stack('preload')
    @yield('styles')
    @stack('styles')
</head>
<body class="@yield('body-class', 'bg-dark text-light')">
    <div id="app" class="min-vh-100 d-flex flex-column">
        @includeWhen(View::exists('layouts.partials.navigation'), 'layouts.partials.navigation')

        @if (trim($__env->yieldContent('header')))
            <header>
                @yield('header')
            </header>
        @endif

        <main class="flex-fill">
            @yield('content')
        </main>

        @hasSection('footer')
            <footer>
                @yield('footer')
            </footer>
        @endif
    </div>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
