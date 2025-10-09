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

    {{-- Authentication screens keep using the existing compiled assets. --}}
    @yield('styles')
    @stack('styles')
</head>
<body class="@yield('body-class', 'guest-layout bg-dark text-light')">
    <div id="guest" class="min-vh-100 d-flex flex-column justify-content-center align-items-center">
        <main class="w-100" style="max-width: 420px;">
            @yield('content')
        </main>

        @hasSection('footer')
            <footer class="mt-4 text-center small text-muted">
                @yield('footer')
            </footer>
        @endif
    </div>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
