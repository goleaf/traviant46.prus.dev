@extends('layouts.app')

@section('title', trim($__env->yieldContent('title', __('Authentication'))))

@section('body_class', 'legacy-body')
@section('app_container_class', 'legacy-shell')

@section('content')
    <div class="legacy-auth-grid">
        <section class="outerLoginBox @yield('outer_login_box_classes')">
            <h2>@yield('title')</h2>

            @hasSection('subtitle')
                <p class="legacy-subtitle">@yield('subtitle')</p>
            @endif

            <noscript>
                <div class="legacy-noscript">
                    {{ __('Travian requires JavaScript to deliver real-time world updates. Please enable it to continue.') }}
                </div>
            </noscript>

            @if (session('status'))
                <div class="legacy-callout legacy-callout--success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="legacy-callout legacy-callout--danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="innerLoginBox">
                @yield('content')
            </div>

            @hasSection('footer')
                <div class="legacy-footer">
                    @yield('footer')
                </div>
            @endif
        </section>

        <aside class="legacy-auth-aside">
            @hasSection('aside')
                @yield('aside')
            @else
                <h3>{{ __('The Travian legacy returns') }}</h3>
                <p>
                    {{ __('Rebuilt on Laravel 12 with Fortify, the classic Travian interface keeps its timeless strategy aesthetic while adopting modern security and performance foundations.') }}
                </p>
                <ul>
                    <li>{{ __('Preserve the familiar empire management layout veterans love.') }}</li>
                    <li>{{ __('Hardened authentication powered by Laravel Fortify and session protections.') }}</li>
                    <li>{{ __('Seamless access to dashboards, sitter controls, and alliance coordination tools.') }}</li>
                </ul>
            @endif
        </aside>
    </div>
@endsection
