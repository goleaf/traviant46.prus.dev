@extends('layouts.minimal')

@section('title', __('auth.verification.title'))

@section('content')
    <main class="w-full max-w-md space-y-8 rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-xl shadow-amber-900/15 backdrop-blur">
        <header class="space-y-2 text-center">
            <h1 class="text-2xl font-semibold text-white">
                {{ __('auth.verification.heading') }}
            </h1>
            <p class="text-sm text-slate-300">
                {{ __('auth.verification.subtitle') }}
            </p>
        </header>

        <section class="space-y-3 text-sm text-slate-300">
            <p>{{ __('auth.verification.instructions') }}</p>

            <div class="rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('auth.verification.current_email_label') }}</p>
                <p class="mt-1 font-semibold text-white">
                    {{ auth()->user()?->email }}
                </p>
            </div>
        </section>

        @if (session('status') === 'verification-link-sent')
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100" role="status">
                {{ __('auth.verification.status.resent') }}
            </div>
        @endif

        <div class="space-y-3">
            <form method="POST" action="{{ route('verification.send') }}" class="space-y-3" novalidate>
                @csrf
                <button type="submit" class="flex w-full justify-center rounded-xl bg-amber-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-200">
                    {{ __('auth.verification.actions.resend') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="space-y-3" novalidate>
                @csrf
                <button type="submit" class="flex w-full justify-center rounded-xl border border-white/15 bg-transparent px-4 py-3 text-sm font-semibold text-white transition hover:border-amber-200 hover:text-amber-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-200">
                    {{ __('auth.verification.actions.logout') }}
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-slate-400">
            {{ __('auth.verification.support_prompt') }}
            <a href="mailto:support@travian.example" class="font-semibold text-amber-200 hover:text-amber-100">
                {{ __('auth.verification.support_cta') }}
            </a>
        </p>
    </main>
@endsection
