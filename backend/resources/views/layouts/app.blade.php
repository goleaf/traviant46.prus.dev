<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Travian Backend') }}</title>
    @livewireStyles
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; }
        header { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(148, 163, 184, 0.3); }
        nav { display: flex; gap: 1rem; align-items: center; padding: 1rem 2rem; }
        nav a { color: #cbd5f5; text-decoration: none; font-weight: 600; }
        nav a:hover { color: #f8fafc; }
        main { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
        .card { background: rgba(15, 23, 42, 0.65); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem; }
        h1, h2, h3 { margin-top: 0; color: #f8fafc; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; border-bottom: 1px solid rgba(148, 163, 184, 0.2); text-align: left; }
        th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
        td { color: #e2e8f0; font-size: 0.95rem; }
    </style>
</head>
<body>
<header>
    <nav>
        <a href="{{ route('home') }}">Dashboard</a>
        <a href="{{ route('villages.overview') }}">Village</a>
        <a href="{{ route('hero.overview') }}">Hero</a>
        <a href="{{ route('alliance.tools') }}">Alliance</a>
        <a href="{{ route('sitters.index') }}">Sitters</a>
    </nav>
</header>
<main>
    {{ $slot }}
</main>
@livewireScripts
</body>
</html>
