{{-- Shared text-only layout for Travian game surfaces. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php($cspNonce = app()->has('csp.nonce') ? app('csp.nonce') : null)
        @php
            /** @var string $title */
            $resolvedTitle = $title ?? trim($__env->yieldContent('title'));
        @endphp
        <title>{{ $resolvedTitle !== '' ? $resolvedTitle : __('Game Console') . ' • ' . config('app.name', 'Travian T4.6') }}</title>
        <style @if($cspNonce) nonce="{{ $cspNonce }}" @endif>
            :root {
                color-scheme: light dark;
            }

            body {
                margin: 0;
                padding: 1.5rem;
                font-family: "Fira Mono", "Source Code Pro", Menlo, Monaco, Consolas, "Courier New", monospace;
                background-color: #0f172a;
                color: #e2e8f0;
                line-height: 1.6;
            }

            a {
                color: inherit;
                text-decoration: underline;
            }

            main {
                display: block;
                max-width: 64rem;
            }

            header,
            footer {
                margin-bottom: 1rem;
            }

            pre,
            code {
                font-family: inherit;
            }
        </style>

        @livewireStyles(['nonce' => $cspNonce])
        @livewireScriptConfig(['nonce' => $cspNonce])

        @stack('head')
    </head>
    <body>
        <header>
            {{ $header ?? '' }}
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer>
            {{ $footer ?? '' }}
        </footer>

        @livewireScripts(['nonce' => $cspNonce])
        @stack('scripts')
    </body>
</html>
