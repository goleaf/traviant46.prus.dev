@extends('layouts.app')

@section('title', __('Two-Factor Authentication'))

@section('content')
    <div class="relative flex flex-1 overflow-hidden bg-slate-950/95">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.12),_transparent_45%)]"></div>
        <div class="pointer-events-none absolute inset-y-0 left-1/2 -z-10 w-[110%] -translate-x-1/2 bg-[radial-gradient(circle,_rgba(12,74,110,0.16),_transparent_60%)]"></div>

        <flux:container class="w-full py-16">
            <div class="space-y-10">
                <div class="max-w-3xl space-y-4">
                    <flux:heading level="1" size="xl" class="text-white">
                        {{ __('Two-factor authentication') }}
                    </flux:heading>
                    <flux:description class="text-slate-300">
                        {{ __('Protect privileged accounts by requiring a time-based verification code in addition to your password. Admin and multihunter roles must keep two-factor enabled.') }}
                    </flux:description>
                </div>

                @php
                    $status = session('status');
                @endphp

                @if ($status)
                    <flux:callout :variant="$status === 'two-factor-required' ? 'warning' : 'success'">
                        @switch($status)
                            @case('two-factor-required')
                                {{ __('Two-factor authentication is required before you can access sensitive tooling. Set it up below.') }}
                                @break
                            @case(\Laravel\Fortify\Fortify::TWO_FACTOR_AUTHENTICATION_ENABLED)
                                {{ __('Two-factor authentication was enabled. Scan the QR code and confirm a code to finish.') }}
                                @break
                            @case(\Laravel\Fortify\Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED)
                                {{ __('Two-factor authentication is confirmed and active on your account.') }}
                                @break
                            @case(\Laravel\Fortify\Fortify::TWO_FACTOR_AUTHENTICATION_DISABLED)
                                {{ __('Two-factor authentication was disabled for this account.') }}
                                @break
                            @default
                                {{ __($status) }}
                        @endswitch
                    </flux:callout>
                @endif

                @if ($errors->any())
                    <flux:callout variant="danger">
                        <ul class="list-disc space-y-1 pl-5 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </flux:callout>
                @endif

                <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <div class="space-y-6">
                        <section class="rounded-3xl border border-white/10 bg-white/5 p-8 backdrop-blur">
                            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                                <div>
                                    <flux:heading level="2" size="lg" class="text-white">
                                        {{ __('Status') }}
                                    </flux:heading>
                                    <p class="mt-2 text-sm text-slate-300">
                                        {{ __('Two-factor authentication adds a rotating six-digit code from an authenticator app.') }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-slate-900/70 px-3 py-1 text-xs font-medium uppercase tracking-wide text-slate-200">
                                        {{ $twoFactorEnabled ? __('Enabled') : __('Disabled') }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-slate-900/70 px-3 py-1 text-xs font-medium uppercase tracking-wide {{ $twoFactorConfirmed ? 'text-emerald-300' : 'text-amber-300' }}">
                                        {{ $twoFactorConfirmed ? __('Confirmed') : __('Awaiting confirmation') }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                                @if (! $twoFactorEnabled)
                                    <form method="POST" action="{{ route('two-factor.enable') }}" class="sm:w-auto">
                                        @csrf
                                        <flux:button type="submit" variant="primary" color="sky" icon="shield-check">
                                            {{ __('Enable two-factor') }}
                                        </flux:button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('two-factor.disable') }}" class="sm:w-auto">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button type="submit" variant="ghost" color="amber" icon="shield-exclamation">
                                            {{ __('Disable two-factor') }}
                                        </flux:button>
                                    </form>

                                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}" class="sm:w-auto">
                                        @csrf
                                        <flux:button type="submit" variant="ghost" icon="key">
                                            {{ __('Regenerate recovery codes') }}
                                        </flux:button>
                                    </form>
                                @endif
                            </div>
                        </section>

                        @if ($twoFactorEnabled)
                            <section class="rounded-3xl border border-white/10 bg-slate-900/50 p-8 backdrop-blur">
                                <flux:heading level="2" size="lg" class="text-white">
                                    {{ __('Authenticator setup') }}
                                </flux:heading>
                                <p class="mt-2 text-sm text-slate-300">
                                    {{ __('Scan the QR code with your authenticator app or enter the secret manually, then submit the six-digit code to confirm.') }}
                                </p>

                                <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start">
                                    <div class="flex h-48 w-full items-center justify-center rounded-2xl bg-slate-950/70 p-4 lg:w-64">
                                        @if ($qrCodeSvg)
                                            <div class="[&_svg]:h-full [&_svg]:w-full [&_svg_path]:fill-white/90" aria-label="{{ __('Authenticator QR code') }}">
                                                {!! $qrCodeSvg !!}
                                            </div>
                                        @else
                                            <p class="text-center text-sm text-slate-400">{{ __('QR code will appear after enabling two-factor authentication.') }}</p>
                                        @endif
                                    </div>

                                    <div class="flex-1">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            {{ __('Manual entry key') }}
                                        </p>
                                        <code class="mt-2 inline-flex select-all rounded-lg bg-slate-900/70 px-4 py-2 text-sm tracking-widest text-slate-100">
                                            {{ $secretKey ?? __('Unavailable') }}
                                        </code>

                                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-6 space-y-4">
                                            @csrf
                                            <flux:field label="{{ __('Six-digit code') }}" for="two_factor_code">
                                                <flux:input
                                                    id="two_factor_code"
                                                    name="code"
                                                    inputmode="numeric"
                                                    autocomplete="one-time-code"
                                                    maxlength="6"
                                                    required
                                                    placeholder="123456"
                                                />
                                            </flux:field>
                                            <flux:button type="submit" variant="primary" color="emerald" icon="check">
                                                {{ __('Confirm setup') }}
                                            </flux:button>
                                        </form>
                                    </div>
                                </div>
                            </section>
                        @endif
                    </div>

                    <aside class="space-y-6">
                        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6 backdrop-blur">
                            <flux:heading level="3" size="md" class="text-white">
                                {{ __('Recovery codes') }}
                            </flux:heading>
                            <p class="mt-2 text-sm text-slate-300">
                                {{ __('Store these one-time codes in a secure location. They bypass two-factor if your authenticator is unavailable.') }}
                            </p>

                            <div class="mt-4 grid gap-2 rounded-xl bg-slate-950/70 p-4 text-sm text-slate-100">
                                @forelse ($recoveryCodes as $code)
                                    <code class="tracking-wider">{{ $code }}</code>
                                @empty
                                    <p class="text-slate-400">{{ __('Recovery codes will appear after enabling two-factor authentication.') }}</p>
                                @endforelse
                            </div>
                        </section>

                        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6 backdrop-blur">
                            <flux:heading level="3" size="md" class="text-white">
                                {{ __('Device security tips') }}
                            </flux:heading>
                            <ul class="mt-3 space-y-2 text-sm text-slate-300">
                                <li>{{ __('Install authenticator apps on at least two devices for redundancy.') }}</li>
                                <li>{{ __('Rotate recovery codes after using one and store them offline.') }}</li>
                                <li>{{ __('Enable OS-level protections such as biometrics or hardware keys when available.') }}</li>
                            </ul>
                        </section>
                    </aside>
                </div>
            </div>
        </flux:container>
    </div>
@endsection
