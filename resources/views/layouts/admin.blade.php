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

    {{-- Admin panel assets remain opt-in to follow the existing build. --}}
    @stack('preload')
    @yield('styles')
    @stack('styles')
</head>
<body class="@yield('body-class', 'admin-panel bg-dark text-light')">
    <div id="admin" class="min-vh-100 d-flex">
        @if (trim($__env->yieldContent('sidebar')))
            <aside class="admin-sidebar">
                @yield('sidebar')
            </aside>
        @endif

        <div class="admin-content flex-grow-1 d-flex flex-column">
            @if (trim($__env->yieldContent('topbar')))
                <header class="admin-topbar">
                    @yield('topbar')
                </header>
            @endif

            <main class="flex-fill">
                @yield('content')
            </main>

            @hasSection('footer')
                <footer class="admin-footer">
                    @yield('footer')
                </footer>
            @endif
        </div>
    </div>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
