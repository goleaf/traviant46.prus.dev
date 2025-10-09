<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name') }} – Auth</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 420px; margin: 4rem auto; padding: 2.5rem; background: white; border-radius: 1rem; box-shadow: 0 15px 35px rgba(15, 23, 42, 0.1); }
        h1 { font-size: 1.5rem; margin-bottom: 1.5rem; color: #111827; }
        label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #111827; }
        input { width: 100%; padding: 0.75rem 1rem; border-radius: 0.75rem; border: 1px solid #d1d5db; background: #f9fafb; transition: border-color 0.2s ease; }
        input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); background: #fff; }
        button { width: 100%; padding: 0.85rem; border-radius: 0.75rem; border: none; background: linear-gradient(135deg, #6366f1, #4338ca); color: white; font-weight: 600; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        button:hover { transform: translateY(-1px); box-shadow: 0 10px 20px rgba(79, 70, 229, 0.25); }
        .link { display: block; margin-top: 1rem; text-align: center; color: #4f46e5; text-decoration: none; font-weight: 500; }
        .errors { background: #fee2e2; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 0.75rem; margin-bottom: 1.25rem; }
        ul { margin: 0; padding-left: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>@yield('title')</h1>
    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @yield('content')
</div>
</body>
</html>
