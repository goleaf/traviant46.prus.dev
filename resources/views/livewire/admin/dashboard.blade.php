@php
    $summaryCards = $summaryCards ?? [];
    $secondaryCards = $secondaryCards ?? [];
    $recentUsers = $recentUsers ?? [];
    $sessions = $sessions ?? [];
    $sitterDelegations = $sitterDelegations ?? [];
    $alerts = $alerts ?? [];
    $auditTrail = $auditTrail ?? [];
    $isImpersonating = session()->get('impersonation.active', false);
    $adminUserId = auth()->id();
@endphp

<div class="relative flex flex-1 overflow-hidden">
    <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.12),_transparent_45%)]"></div>
    <div class="pointer-events-none absolute inset-y-0 left-1/2 -z-10 w-[110%] -translate-x-1/2 bg-[radial-gradient(circle,_rgba(14,165,233,0.08),_transparent_60%)]"></div>

    <flux:container class="w-full py-14">
        <div class="space-y-10">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-8 shadow-[0_35px_120px_-40px_rgba(56,189,248,0.45)] backdrop-blur">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
                    <div class="space-y-2">
                        <flux:heading level="1" size="xl" class="text-white">
                            {{ __('Administration command center') }}
                        </flux:heading>
                        <flux:description class="max-w-3xl text-slate-300">
                            {{ __('Monitor account health, sitter oversight, and security events without leaving Livewire.') }}
                        </flux:description>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <flux:button
                            wire:click="refreshData"
                            wire:target="refreshData"
                            wire:loading.attr="disabled"
                            icon="arrow-path"
                            variant="outline"
                            class="w-full sm:w-auto"
                        >
                            <span wire:loading.class="hidden" wire:target="refreshData">{{ __('Refresh data') }}</span>
                            <span wire:loading wire:target="refreshData">{{ __('Refreshing…') }}</span>
                        </flux:button>
                        <span class="text-xs uppercase tracking-wide text-slate-400">
                            {{ __('Updated :time ago', ['time' => $refreshedAt ?: __('moments')]) }}
                        </span>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($summaryCards as $card)
                        <div
                            wire:key="summary-card-{{ \Illuminate\Support\Str::slug($card['label']) }}"
                            class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 shadow-inner shadow-black/20"
                        >
                            <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                                <span>{{ $card['label'] }}</span>
                                <flux:icon name="{{ $card['icon'] }}" class="size-4 text-{{ $card['color'] }}-300" />
                            </div>
                            <div class="mt-3 text-3xl font-semibold text-white">
                                {{ number_format($card['value']) }}
                            </div>
                            <p class="mt-2 text-xs text-slate-400">
                                {{ $card['description'] }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach ($secondaryCards as $card)
                        <div
                            wire:key="secondary-card-{{ \Illuminate\Support\Str::slug($card['label']) }}"
                            class="rounded-2xl border border-white/10 bg-slate-950/40 p-4"
                        >
                            <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                                <span>{{ $card['label'] }}</span>
                                <flux:icon name="{{ $card['icon'] }}" class="size-3.5 text-{{ $card['color'] }}-300" />
                            </div>
                            <div class="mt-2 text-2xl font-semibold text-white">
                                {{ number_format($card['value']) }}
                            </div>
                            <p class="mt-1 text-[11px] leading-5 text-slate-400">
                                {{ $card['description'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            @error('impersonation')
                <flux:callout variant="danger" icon="exclamation-triangle" class="rounded-3xl border border-rose-500/30 bg-rose-500/10 p-5 text-rose-100">
                    {{ $message }}
                </flux:callout>
            @enderror

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <flux:heading size="lg" class="text-white">
                                {{ __('Newest player registrations') }}
                            </flux:heading>
                            <flux:description class="text-slate-400">
                                {{ __('Track the latest arrivals, their verification state, and legacy alignment.') }}
                            </flux:description>
                        </div>
                    </div>

                    @if ($recentUsers === [])
                        <flux:callout variant="subtle" icon="information-circle" class="mt-6 text-slate-200">
                            {{ __('No recent registrations detected.') }}
                        </flux:callout>
                    @else
                        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10">
                            <table class="min-w-full divide-y divide-white/10 text-left text-sm text-slate-200">
                                <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">{{ __('Player') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Legacy UID') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Created') }}</th>
                                        <th class="px-4 py-3 font-semibold text-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach ($recentUsers as $user)
                                        <tr wire:key="recent-user-{{ $user['id'] }}" class="bg-slate-950/40 hover:bg-slate-900/60">
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-white">{{ $user['username'] }}</div>
                                                <div class="text-xs text-slate-400">{{ $user['email'] }}</div>
                                            </td>
                                            <td class="px-4 py-3 font-mono text-xs text-sky-300">
                                                {{ $user['legacy_uid'] ?? __('n/a') }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <flux:badge variant="solid" :color="$user['status_color']">
                                                    {{ $user['status'] }}
                                                </flux:badge>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-300">
                                                <div>{{ $user['created_at'] }}</div>
                                                <div class="text-[11px] text-slate-500">{{ __(':time ago', ['time' => $user['created_diff']]) }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                @if (! $isImpersonating && isset($user['id']) && $adminUserId !== $user['id'] && $user['legacy_uid'] !== \App\Models\User::LEGACY_ADMIN_UID && $user['legacy_uid'] !== \App\Models\User::LEGACY_MULTIHUNTER_UID)
                                                    <flux:button
                                                        wire:click="startImpersonation({{ $user['id'] }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="startImpersonation"
                                                        variant="outline"
                                                        size="xs"
                                                        class="border-sky-500/40 text-sky-200 hover:border-sky-300 hover:text-sky-50"
                                                    >
                                                        {{ __('Impersonate') }}
                                                    </flux:button>
                                                @else
                                                    <span class="text-xs text-slate-500">{{ __('Not available') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <flux:heading size="lg" class="text-white">
                                {{ __('Active session inventory') }}
                            </flux:heading>
                            <flux:description class="text-slate-400">
                                {{ __('Review the most recent browser sessions and their activity.') }}
                            </flux:description>
                        </div>
                    </div>

                    @if ($sessions === [])
                        <flux:callout variant="subtle" icon="computer-desktop" class="mt-6 text-slate-200">
                            {{ __('No live sessions detected for the configured window.') }}
                        </flux:callout>
                    @else
                        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10">
                            <table class="min-w-full divide-y divide-white/10 text-left text-sm text-slate-200">
                                <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">{{ __('Account') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('IP address') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Last activity') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('User agent') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach ($sessions as $session)
                                        <tr wire:key="session-{{ $session['id'] }}" class="bg-slate-950/40 hover:bg-slate-900/60">
                                            <td class="px-4 py-3 text-sm text-white">
                                                @if ($session['user'])
                                                    <div class="font-semibold">{{ $session['user']['username'] }}</div>
                                                    <div class="font-mono text-xs text-sky-300">UID {{ $session['user']['legacy_uid'] }}</div>
                                                @else
                                                    <span class="font-mono text-xs text-slate-400">{{ __('Guest') }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 font-mono text-xs text-amber-300">{{ $session['ip'] ?? __('n/a') }}</td>
                                            <td class="px-4 py-3 text-xs text-slate-300">
                                                <div>{{ $session['last_activity'] }}</div>
                                                <div class="text-[11px] text-slate-500">{{ __(':time ago', ['time' => $session['last_diff']]) }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-400">{{ $session['agent'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <flux:heading size="lg" class="text-white">
                                {{ __('Sitter delegations in force') }}
                            </flux:heading>
                            <flux:description class="text-slate-400">
                                {{ __('Highlight the most recently updated sitter relationships and permissions.') }}
                            </flux:description>
                        </div>
                    </div>

                    @if ($sitterDelegations === [])
                        <flux:callout variant="subtle" icon="users" class="mt-6 text-slate-200">
                            {{ __('No active sitter delegations right now.') }}
                        </flux:callout>
                    @else
                        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10">
                            <table class="min-w-full divide-y divide-white/10 text-left text-sm text-slate-200">
                                <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">{{ __('Owner account') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Sitter') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Permissions') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Expires') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach ($sitterDelegations as $delegation)
                                        <tr wire:key="delegation-{{ $delegation['id'] }}" class="bg-slate-950/40 hover:bg-slate-900/60">
                                            <td class="px-4 py-3 text-sm text-white">
                                                <div class="font-semibold">{{ $delegation['owner']['username'] ?? __('Unknown') }}</div>
                                                <div class="font-mono text-xs text-sky-300">UID {{ $delegation['owner']['legacy_uid'] ?? __('n/a') }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-white">
                                                <div class="font-semibold">{{ $delegation['sitter']['username'] ?? __('Unknown') }}</div>
                                                <div class="font-mono text-xs text-slate-300">UID {{ $delegation['sitter']['legacy_uid'] ?? __('n/a') }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-300">
                                                @if ($delegation['permissions'] === [])
                                                    <span class="text-slate-500">{{ __('No special permissions') }}</span>
                                                @else
                                                    <ul class="list-disc space-y-1 pl-4">
                                                        @foreach ($delegation['permissions'] as $permission)
                                                            <li>{{ $permission }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-300">
                                                <div>{{ $delegation['expires_at'] ?? __('Indefinite') }}</div>
                                                @if (! empty($delegation['expires_diff']))
                                                    <div class="text-[11px] text-slate-500">{{ __(':time remaining', ['time' => $delegation['expires_diff']]) }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <flux:heading size="lg" class="text-white">
                                {{ __('Multi-account alerts') }}
                            </flux:heading>
                            <flux:description class="text-slate-400">
                                {{ __('Signals generated by overlapping addresses and unusual co-login patterns.') }}
                            </flux:description>
                        </div>
                    </div>

                    @if ($alerts === [])
                        <flux:callout variant="subtle" icon="shield-exclamation" class="mt-6 text-slate-200">
                            {{ __('No outstanding alerts — great job keeping the world tidy!') }}
                        </flux:callout>
                    @else
                        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10">
                            <table class="min-w-full divide-y divide-white/10 text-left text-sm text-slate-200">
                                <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">{{ __('IP address') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Primary account') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Conflict account') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Occurrences') }}</th>
                                        <th class="px-4 py-3 font-semibold">{{ __('Last seen') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach ($alerts as $alert)
                                        <tr wire:key="alert-{{ $alert['id'] }}" class="bg-slate-950/40 hover:bg-slate-900/60">
                                            <td class="px-4 py-3 font-mono text-xs text-amber-300">{{ $alert['ip'] }}</td>
                                            <td class="px-4 py-3 text-sm text-white">
                                                <div class="font-semibold">{{ $alert['primary_user']['username'] ?? __('Unknown') }}</div>
                                                <div class="font-mono text-xs text-sky-300">UID {{ $alert['primary_user']['legacy_uid'] ?? __('n/a') }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-white">
                                                <div class="font-semibold">{{ $alert['conflict_user']['username'] ?? __('Unknown') }}</div>
                                                <div class="font-mono text-xs text-rose-300">UID {{ $alert['conflict_user']['legacy_uid'] ?? __('n/a') }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm font-semibold text-white">
                                                {{ number_format($alert['occurrences']) }}
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-300">
                                                <div>{{ $alert['last_seen_at'] ?? __('Unknown') }}</div>
                                                @if (! empty($alert['last_seen_diff']))
                                                    <div class="text-[11px] text-slate-500">{{ __(':time ago', ['time' => $alert['last_seen_diff']]) }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <flux:heading size="lg" class="text-white">
                            {{ __('Login audit trail') }}
                        </flux:heading>
                        <flux:description class="text-slate-400">
                            {{ __('Every login attempt, mapped to sitters and origin addresses for incident response.') }}
                        </flux:description>
                    </div>
                </div>

                @if ($auditTrail === [])
                    <flux:callout variant="subtle" icon="document-text" class="mt-6 text-slate-200">
                        {{ __('No login events recorded in the current window.') }}
                    </flux:callout>
                @else
                    <div class="mt-6 overflow-hidden rounded-2xl border border-white/10">
                        <table class="min-w-full divide-y divide-white/10 text-left text-sm text-slate-200">
                            <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">{{ __('Account') }}</th>
                                    <th class="px-4 py-3 font-semibold">{{ __('Actor') }}</th>
                                    <th class="px-4 py-3 font-semibold">{{ __('IP address') }}</th>
                                    <th class="px-4 py-3 font-semibold">{{ __('Context') }}</th>
                                    <th class="px-4 py-3 font-semibold">{{ __('Timestamp') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach ($auditTrail as $event)
                                    <tr wire:key="audit-{{ $event['id'] }}" class="bg-slate-950/40 hover:bg-slate-900/60">
                                        <td class="px-4 py-3 text-sm text-white">
                                            <div class="font-semibold">{{ $event['user']['username'] ?? __('Unknown') }}</div>
                                            <div class="font-mono text-xs text-sky-300">UID {{ $event['user']['legacy_uid'] ?? __('n/a') }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-white">
                                            @if ($event['actor'])
                                                <div class="font-semibold">{{ $event['actor']['username'] }}</div>
                                                <div class="font-mono text-xs text-violet-300">UID {{ $event['actor']['legacy_uid'] }}</div>
                                            @else
                                                <span class="text-xs text-slate-400">{{ __('Account owner') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-amber-300">{{ $event['ip'] ?? __('n/a') }}</td>
                                        <td class="px-4 py-3 text-xs text-slate-300">
                                            @if ($event['via_sitter'])
                                                <flux:badge variant="solid" color="violet">{{ __('Sitter login') }}</flux:badge>
                                            @else
                                                <flux:badge variant="solid" color="sky">{{ __('Direct login') }}</flux:badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-300">
                                            <div>{{ $event['timestamp'] ?? __('Unknown') }}</div>
                                            @if (! empty($event['diff']))
                                                <div class="text-[11px] text-slate-500">{{ __(':time ago', ['time' => $event['diff']]) }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </flux:container>
</div>
