@extends('layouts.minimal')

@section('title', __('auth.passwords.email.title'))

@section('content')
    <main class="w-full max-w-md space-y-8 rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-xl shadow-amber-900/15 backdrop-blur">
        <header class="space-y-2 text-center">
            <h1 class="text-2xl font-semibold text-white">
                {{ __('auth.passwords.email.heading') }}
            </h1>
            <p class="text-sm text-slate-300">
                {{ __('auth.passwords.email.subtitle') }}
            </p>
        </header>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100" role="alert">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6" autocomplete="on" novalidate>
            @csrf

            <div class="space-y-2">
                <label for="email" class="block text-sm font-medium text-slate-200">
                    {{ __('auth.passwords.email.label') }}
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="w-full rounded-xl border border-white/15 bg-slate-950/60 px-4 py-3 text-sm text-white placeholder-slate-500 shadow-inner focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/60"
                    required
                    autofocus
                    autocomplete="email"
                    @if ($errors->has('email'))
                        aria-invalid="true"
                        aria-describedby="email-error"
                    @endif
                >
                @error('email')
                    <p id="email-error" class="text-sm text-rose-200">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <button type="submit" class="flex w-full justify-center rounded-xl bg-amber-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-200">
                {{ __('auth.passwords.email.actions.submit') }}
            </button>
        </form>

        <nav class="flex flex-col gap-2 text-center text-sm text-slate-300">
            <a href="{{ route('login') }}" class="font-medium text-amber-200 hover:text-amber-100">
                {{ __('auth.passwords.links.login') }}
            </a>
            <a href="{{ route('register') }}" class="font-medium text-amber-200 hover:text-amber-100">
                {{ __('auth.passwords.links.register') }}
            </a>
        </nav>
    </main>
@endsection
