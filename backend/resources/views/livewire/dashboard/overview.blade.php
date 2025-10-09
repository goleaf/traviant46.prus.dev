@php
    $activeSitters = data_get($metrics, 'activeSitters', 0);
    $delegatedAccounts = data_get($metrics, 'delegatedAccounts', 0);
    $twoFactorEnabled = data_get($metrics, 'twoFactorEnabled', false);
    $recentLogins = data_get($metrics, 'recentLogins', []);
@endphp

<div class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_30px_80px_-40px_rgba(15,23,42,0.65)] backdrop-blur">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="space-y-2">
            <flux:heading size="lg" class="text-white">
                {{ __('Security & sitter insights') }}
            </flux:heading>
            <flux:description class="text-slate-400">
                {{ __('Track who can act on your account, how many alliances you support, and the latest sign-ins.') }}
            </flux:description>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:button wire:click="refreshMetrics" wire:target="refreshMetrics" icon="arrow-path" variant="outline" class="w-full sm:w-auto">
                {{ __('Refresh data') }}
            </flux:button>
            <span class="text-xs uppercase tracking-wide text-slate-500">
                {{ __('Updated :time ago', ['time' => $refreshedAt ?: __('moments')]) }}
            </span>
        </div>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
            <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                <span>{{ __('Active sitters') }}</span>
                <flux:icon name="user-group" class="size-4 text-sky-300" />
            </div>
            <div class="mt-3 text-2xl font-semibold text-white">
                {{ number_format($activeSitters) }}
            </div>
            <p class="mt-2 text-xs text-slate-400">
                {{ __('Players who can currently manage your villages with granted permissions.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
            <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                <span>{{ __('Accounts you sit') }}</span>
                <flux:icon name="briefcase" class="size-4 text-purple-300" />
            </div>
            <div class="mt-3 text-2xl font-semibold text-white">
                {{ number_format($delegatedAccounts) }}
            </div>
            <p class="mt-2 text-xs text-slate-400">
                {{ __('Allied accounts relying on your help; keep activity transparent to avoid penalties.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
            <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                <span>{{ __('Two-factor enforcement') }}</span>
                <flux:icon name="lock-closed" class="size-4 text-emerald-300" />
            </div>
            <div class="mt-3 flex items-center gap-3">
                @if ($twoFactorEnabled)
                    <flux:badge variant="solid" color="emerald">
                        {{ __('Enabled') }}
                    </flux:badge>
                @else
                    <flux:badge variant="solid" color="red">
                        {{ __('Disabled') }}
                    </flux:badge>
                @endif
                <span class="text-sm text-slate-300">
                    {{ $twoFactorEnabled ? __('All high-risk actions require confirmation codes.') : __('Set up 2FA to protect premium purchases and sitter approvals.') }}
                </span>
            </div>
        </div>
    </div>

    <flux:separator class="my-8" />

    <div class="space-y-4">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <flux:heading size="lg" class="text-white">
                {{ __('Recent login activity') }}
            </flux:heading>
            <span class="text-xs uppercase tracking-wide text-slate-500">
                {{ __('Includes sitter sessions and direct logins') }}
            </span>
        </div>

        @if (empty($recentLogins))
            <flux:callout variant="subtle" icon="information-circle" class="text-slate-200">
                {{ __('No login attempts recorded yet. Once you or a sitter signs in, the history will appear here.') }}
            </flux:callout>
        @else
            <div class="overflow-hidden rounded-2xl border border-white/10">
                <table class="min-w-full divide-y divide-white/10 text-left text-sm text-slate-200">
                    <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('IP address') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Session type') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Timestamp') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($recentLogins as $login)
                            <tr wire:key="login-{{ $login['id'] }}" class="bg-slate-950/40 hover:bg-slate-900/60">
                                <td class="px-4 py-3 font-mono text-[13px] text-sky-200">{{ $login['ip'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if ($login['via_sitter'])
                                            <flux:badge variant="solid" color="violet">{{ __('Sitter') }}</flux:badge>
                                        @else
                                            <flux:badge variant="solid" color="sky">{{ __('Owner') }}</flux:badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-300">{{ $login['timestamp'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
