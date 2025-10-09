<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: rgba(15, 23, 42, 0.85); padding: 3rem; border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(30, 64, 175, 0.35); max-width: 520px; text-align: center; }
        h1 { font-size: 2rem; margin-bottom: 1rem; }
        p { margin-bottom: 2rem; line-height: 1.6; }
        a { display: inline-block; margin: 0.5rem; padding: 0.85rem 1.5rem; border-radius: 9999px; text-decoration: none; font-weight: 600; }
        .primary { background: linear-gradient(135deg, #38bdf8, #6366f1); color: #0f172a; }
        .secondary { background: rgba(148, 163, 184, 0.2); color: #e2e8f0; border: 1px solid rgba(148, 163, 184, 0.3); }
    </style>
</head>
<body>
<div class="card">
    <h1>{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</h1>
    <p>{{ __('You are signed in with the new Fortify powered authentication stack. Email verification, sitter management, and multi-account safeguards are now enabled.') }}</p>
    <a href="{{ route('sitters.index') }}" class="primary">{{ __('Manage sitters') }}</a>
    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="secondary" style="border:none; cursor:pointer;">{{ __('Sign out') }}</button>
    </form>
</div>
</body>
</html>
