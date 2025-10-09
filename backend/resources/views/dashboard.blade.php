@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
    <div class="relative flex flex-1 overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.12),_transparent_45%)]"></div>
        <div class="pointer-events-none absolute inset-y-0 left-1/2 -z-10 w-[110%] -translate-x-1/2 bg-[radial-gradient(circle,_rgba(14,165,233,0.08),_transparent_60%)]"></div>

        <flux:container class="w-full py-16">
            <div class="space-y-10">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-8 shadow-[0_35px_120px_-40px_rgba(56,189,248,0.45)] backdrop-blur">
                    <div class="flex flex-col gap-8 md:flex-row md:items-center md:justify-between">
                        <div class="space-y-3">
                            <flux:heading level="1" size="xl" class="text-white">
                                {{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}
                            </flux:heading>
                            <flux:description class="max-w-2xl text-slate-300">
                                {{ __('You are signed in with the Fortify-powered authentication stack. Two-factor, sitter oversight, and premium alliance tooling are ready to use.') }}
                            </flux:description>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <flux:button as="a" href="{{ route('sitters.index') }}" icon="users" variant="primary" color="sky" class="w-full sm:w-auto">
                                {{ __('Manage sitters') }}
                            </flux:button>

                            <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
                                @csrf
                                <flux:button type="submit" variant="ghost" class="w-full sm:w-auto">
                                    {{ __('Sign out') }}
                                </flux:button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                            <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                                <span>{{ __('Email status') }}</span>
                                <flux:icon name="envelope" class="size-4 text-sky-300" />
                            </div>
                            <div class="mt-3 text-xl font-semibold text-white">
                                {{ auth()->user()->hasVerifiedEmail() ? __('Verified') : __('Pending verification') }}
                            </div>
                            <p class="mt-2 text-xs text-slate-400">
                                {{ __('Verified email enables alliance broadcasts and sensitive account events.') }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                            <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                                <span>{{ __('Two-factor auth') }}</span>
                                <flux:icon name="shield-check" class="size-4 text-emerald-300" />
                            </div>
                            <div class="mt-3 text-xl font-semibold text-white">
                                {{ auth()->user()->two_factor_secret ? __('Enabled') : __('Not configured') }}
                            </div>
                            <p class="mt-2 text-xs text-slate-400">
                                {{ __('Protect your world progress by enabling passkey or TOTP-based login approvals.') }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                            <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                                <span>{{ __('Account age') }}</span>
                                <flux:icon name="calendar-days" class="size-4 text-indigo-300" />
                            </div>
                            <div class="mt-3 text-xl font-semibold text-white">
                                {{ optional(auth()->user()->created_at)->diffForHumans(['parts' => 2]) ?? __('New recruit') }}
                            </div>
                            <p class="mt-2 text-xs text-slate-400">
                                {{ __('Longevity boosts your alliance trust score and sitter allowances.') }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                            <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                                <span>{{ __('Primary village') }}</span>
                                <flux:icon name="home-modern" class="size-4 text-orange-300" />
                            </div>
                            <div class="mt-3 text-xl font-semibold text-white">
                                {{ optional(auth()->user()->primary_village ?? null)?->name ?? __('Unassigned') }}
                            </div>
                            <p class="mt-2 text-xs text-slate-400">
                                {{ __('Link a primary village to unlock quick-jump navigation inside the modern UI.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <livewire:dashboard.overview />
            </div>
        </flux:container>
    </div>
@endsection
