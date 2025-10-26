@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/scss/game.scss', 'resources/js/app.js'])
@else
    @include('layouts.partials.fallback-styles')
@endif
