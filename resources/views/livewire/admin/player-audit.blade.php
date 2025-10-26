@php
    $hasReport = filled($auditReport);
    $hasPlayer = $player !== null;
@endphp

<div class="relative flex flex-1 overflow-hidden">
    <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.12),_transparent_45%)]"></div>
    <div class="pointer-events-none absolute inset-y-0 left-1/2 -z-10 w-[110%] -translate-x-1/2 bg-[radial-gradient(circle,_rgba(14,165,233,0.08),_transparent_60%)]"></div>

    <flux:container class="w-full py-14">
        <div class="space-y-8">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-8 shadow-[0_35px_120px_-40px_rgba(56,189,248,0.45)] backdrop-blur">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <flux:heading level="1" size="xl" class="text-white">
                            {{ __('Player audit console') }}
                        </flux:heading>
                        <flux:description class="max-w-3xl text-slate-300">
                            {{ __('Search for a player by username, UID, or email to compile a quick security and activity digest.') }}
                        </flux:description>
                    </div>
                </div>

                <form wire:submit.prevent="lookupPlayer" class="mt-8 space-y-5">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,3fr)_minmax(0,1fr)] lg:items-end">
                        <flux:field
                            label="{{ __('Player lookup') }}"
                            hint="{{ __('Supports partial usernames, legacy UIDs, and email addresses.') }}"
                        >
                            <flux:input
                                wire:model.defer="lookup"
                                placeholder="{{ __('e.g. gaul-warrior, 12345, player@example.com') }}"
                                autocomplete="off"
                            />
                        </flux:field>
                        <flux:button
                            type="submit"
                            icon="magnifying-glass"
                            class="w-full lg:w-auto"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.class="hidden" wire:target="lookupPlayer">{{ __('Run lookup') }}</span>
                            <span wire:loading wire:target="lookupPlayer">{{ __('Searching…') }}</span>
                        </flux:button>
                    </div>
                    @error('lookup')
                        <p class="text-sm text-rose-300">{{ $message }}</p>
                    @enderror
                </form>
            </div>

            @if ($errorMessage)
                <flux:callout variant="danger" icon="exclamation-triangle" class="rounded-3xl border border-rose-500/30 bg-rose-500/10 p-5 text-rose-100">
                    {{ $errorMessage }}
                </flux:callout>
            @endif

            @if ($statusMessage && ! $errorMessage)
                <flux:callout variant="success" icon="check-circle" class="rounded-3xl border border-emerald-400/30 bg-emerald-500/15 p-5 text-emerald-100">
                    {{ $statusMessage }}
                </flux:callout>
            @endif

            @if ($matches !== [])
                <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
                    <flux:heading size="md" class="text-white">
                        {{ __('Select a player to audit') }}
                    </flux:heading>
                    <flux:description class="text-slate-400">
                        {{ __('Multiple accounts matched the lookup. Pick one to generate the audit transcript.') }}
                    </flux:description>

                    <div class="mt-5 grid gap-4">
                        @foreach ($matches as $match)
                            <div
                                wire:key="match-{{ $match['id'] }}"
                                class="flex flex-col gap-3 rounded-2xl border border-white/10 bg-slate-950/70 p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="space-y-1 text-sm text-slate-200">
                                    <div class="text-base font-semibold text-white">
                                        {{ $match['username'] }}
                                    </div>
                                    <div class="font-mono text-xs text-sky-300">
                                        {{ __('UID :uid', ['uid' => $match['legacy_uid'] ?? 'n/a']) }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ $match['email'] ?? __('No email on file') }}
                                    </div>
                                </div>

                                <flux:button
                                    size="sm"
                                    icon="clipboard-document-list"
                                    wire:click="selectUser({{ $match['id'] }})"
                                    wire:loading.attr="disabled"
                                >
                                    {{ __('Load audit') }}
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($hasPlayer)
                <div class="rounded-3xl border border-white/10 bg-slate-950/70 p-6">
                    <flux:heading size="md" class="text-white">
                        {{ __('Account snapshot') }}
                    </flux:heading>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                            <div class="text-xs uppercase tracking-wide text-slate-400">{{ __('Username') }}</div>
                            <div class="mt-2 text-lg font-semibold text-white">{{ $player['username'] }}</div>
                            <div class="mt-1 font-mono text-xs text-sky-300">{{ __('UID :uid', ['uid' => $player['legacy_uid'] ?? 'n/a']) }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                            <div class="text-xs uppercase tracking-wide text-slate-400">{{ __('Email') }}</div>
                            <div class="mt-2 text-sm text-slate-200">{{ $player['email'] ?? __('None') }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                            <div class="text-xs uppercase tracking-wide text-slate-400">{{ __('Tribe') }}</div>
                            <div class="mt-2 text-sm text-slate-200">{{ $player['tribe'] }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                            <div class="text-xs uppercase tracking-wide text-slate-400">{{ __('Registered') }}</div>
                            <div class="mt-2 text-sm text-slate-200">{{ $player['created_at'] }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                            <div class="text-xs uppercase tracking-wide text-slate-400">{{ __('Last login') }}</div>
                            <div class="mt-2 text-sm text-slate-200">
                                @if ($player['last_login_at'] ?? null)
                                    <div>{{ $player['last_login_at'] }}</div>
                                    <div class="font-mono text-xs text-sky-300">{{ $player['last_login_ip'] ?? __('n/a') }}</div>
                                @else
                                    {{ __('Never') }}
                                @endif
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                            <div class="text-xs uppercase tracking-wide text-slate-400">{{ __('Ban status') }}</div>
                            <div class="mt-2">
                                @if ($player['is_banned'])
                                    <flux:badge variant="solid" color="red">
                                        {{ __('Banned') }}
                                    </flux:badge>
                                    @if ($player['ban_reason'])
                                        <div class="mt-2 text-xs text-slate-300">
                                            {{ $player['ban_reason'] }}
                                            @if ($player['ban_expires_at'])
                                                <span class="text-slate-400">{{ __('until :time', ['time' => $player['ban_expires_at']]) }}</span>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <flux:badge variant="solid" color="emerald">{{ __('Active') }}</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($hasReport)
                <div class="rounded-3xl border border-white/10 bg-slate-950/70 p-6">
                    <flux:heading size="md" class="text-white">
                        {{ __('Audit transcript') }}
                    </flux:heading>
                    <flux:description class="text-slate-400">
                        {{ __('Copy-ready text summary for reports or escalation handover.') }}
                    </flux:description>

                    <div class="mt-5">
                        <textarea
                            readonly
                            class="min-h-[320px] w-full rounded-2xl border border-white/10 bg-slate-950/80 p-4 font-mono text-sm leading-6 text-slate-100 focus:outline-none"
                        >{{ $auditReport }}</textarea>
                    </div>
                </div>
            @endif

            @if ($hasPlayer)
                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="space-y-6">
                        <div class="rounded-3xl border border-white/10 bg-slate-950/70 p-6">
                            <flux:heading size="md" class="text-white">
                                {{ __('Villages') }}
                            </flux:heading>
                            @if ($villages === [])
                                <flux:callout variant="subtle" icon="home-modern" class="mt-4 text-slate-200">
                                    {{ __('No villages are currently assigned to this account.') }}
                                </flux:callout>
                            @else
                                <div class="mt-4 space-y-3">
                                    @foreach ($villages as $village)
                                        <div
                                            wire:key="village-{{ $village['id'] }}"
                                            class="rounded-2xl border border-white/10 bg-slate-950/80 p-4 text-sm text-slate-200"
                                        >
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <div class="text-base font-semibold text-white">
                                                        {{ $village['name'] }}
                                                    </div>
                                                    <div class="font-mono text-xs text-sky-300">
                                                        {{ __('KID :kid • :coords', ['kid' => $village['legacy_kid'] ?? 'n/a', 'coords' => $village['coordinates']]) }}
                                                    </div>
                                                </div>
                                                <div class="text-xs text-slate-400">
                                                    {{ __('Pop :pop • Loyalty :loyalty%', ['pop' => $village['population'], 'loyalty' => $village['loyalty']]) }}
                                                </div>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-400">
                                                @if ($village['is_capital'])
                                                    <span class="rounded-full border border-sky-500/40 bg-sky-500/20 px-3 py-1 text-sky-100">{{ __('Capital') }}</span>
                                                @elseif ($village['category'])
                                                    <span class="rounded-full border border-slate-500/40 bg-slate-500/10 px-3 py-1 text-slate-200">
                                                        {{ ucfirst($village['category']) }}
                                                    </span>
                                                @endif
                                                @if ($village['founded_at'])
                                                    <span>{{ __('Founded :time (:diff ago)', ['time' => $village['founded_at'], 'diff' => $village['founded_diff'] ?? __('n/a')]) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-slate-950/70 p-6">
                            <flux:heading size="md" class="text-white">
                                {{ __('Recent movements') }}
                            </flux:heading>
                            @if ($movements === [])
                                <flux:callout variant="subtle" icon="paper-airplane" class="mt-4 text-slate-200">
                                    {{ __('No troop movements registered for this account within the captured window.') }}
                                </flux:callout>
                            @else
                                <div class="mt-4 space-y-3">
                                    @foreach ($movements as $movement)
                                        <div
                                            wire:key="movement-{{ $movement['id'] }}"
                                            class="rounded-2xl border border-white/10 bg-slate-950/80 p-4 text-sm text-slate-200"
                                        >
                                            <div class="flex flex-col gap-2">
                                                <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-slate-400">
                                                    <flux:badge variant="solid" color="sky">
                                                        {{ $movement['movement_type'] }}
                                                    </flux:badge>
                                                    @if ($movement['mission'])
                                                        <span class="rounded-full border border-slate-500/40 bg-slate-500/10 px-2 py-0.5 text-[11px] text-slate-300">
                                                            {{ $movement['mission'] }}
                                                        </span>
                                                    @endif
                                                    <span class="text-slate-500">{{ $movement['status'] }}</span>
                                                </div>
                                                <div class="text-sm text-white">
                                                    {{ $movement['origin'] }} <span class="text-slate-500">→</span> {{ $movement['target'] }}
                                                </div>
                                                <div class="text-xs text-slate-400">
                                                    {{ __('Depart :depart • Arrive :arrive', ['depart' => $movement['depart_at'] ?? __('n/a'), 'arrive' => $movement['arrive_at'] ?? __('n/a')]) }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-3xl border border-white/10 bg-slate-950/70 p-6">
                            <flux:heading size="md" class="text-white">
                                {{ __('Active sessions') }}
                            </flux:heading>
                            @if ($sessions === [])
                                <flux:callout variant="subtle" icon="wifi" class="mt-4 text-slate-200">
                                    {{ __('No active web sessions were found for this account.') }}
                                </flux:callout>
                            @else
                                <div class="mt-4 space-y-3">
                                    @foreach ($sessions as $session)
                                        <div
                                            wire:key="session-{{ $session['id'] }}"
                                            class="rounded-2xl border border-white/10 bg-slate-950/80 p-4 text-sm text-slate-200"
                                        >
                                            <div class="flex flex-col gap-2">
                                                <div class="text-xs uppercase tracking-wide text-slate-400">
                                                    {{ $session['last_activity'] ?? __('Unknown activity time') }}
                                                </div>
                                                <div class="font-mono text-xs text-sky-300">
                                                    {{ $session['ip_address'] ?? __('n/a') }}
                                                </div>
                                                <div class="text-xs text-slate-400">
                                                    {{ $session['user_agent'] ?? __('Unknown user agent') }}
                                                </div>
                                                <div class="text-[11px] text-slate-500">
                                                    {{ __('Expires :time', ['time' => $session['expires_at'] ?? __('n/a')]) }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-slate-950/70 p-6">
                            <flux:heading size="md" class="text-white">
                                {{ __('Recent login activity') }}
                            </flux:heading>
                            @if ($loginActivity === [])
                                <flux:callout variant="subtle" icon="finger-print" class="mt-4 text-slate-200">
                                    {{ __('No login events were located. The retention window may have expired.') }}
                                </flux:callout>
                            @else
                                <div class="mt-4 space-y-3">
                                    @foreach ($loginActivity as $activity)
                                        <div
                                            wire:key="login-{{ $activity['id'] }}"
                                            class="rounded-2xl border border-white/10 bg-slate-950/80 p-4 text-sm text-slate-200"
                                        >
                                            <div class="flex flex-col gap-2">
                                                <div class="text-xs uppercase tracking-wide text-slate-400">
                                                    {{ $activity['logged_at'] ?? __('Unknown') }}
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                                    <span class="font-mono text-sky-300">
                                                        {{ $activity['ip_address'] ?? ($activity['ip_address_hash'] ? 'hash:'.\Illuminate\Support\Str::limit($activity['ip_address_hash'], 16, '') : __('n/a')) }}
                                                    </span>
                                                    @if ($activity['location'])
                                                        <span>{{ $activity['location'] }}</span>
                                                    @endif
                                                    @if ($activity['via_sitter'])
                                                        <span class="rounded-full border border-amber-500/40 bg-amber-500/15 px-2 py-0.5 text-[11px] text-amber-100">
                                                            {{ __('Sitter') }}@if ($activity['sitter']) : {{ $activity['sitter'] }} @endif
                                                        </span>
                                                    @endif
                                                </div>
                                                @if ($activity['user_agent'])
                                                    <div class="text-xs text-slate-500">
                                                        {{ $activity['user_agent'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-slate-950/70 p-6">
                            <flux:heading size="md" class="text-white">
                                {{ __('IP address summary') }}
                            </flux:heading>
                            @if ($ipAddresses === [])
                                <flux:callout variant="subtle" icon="globe-alt" class="mt-4 text-slate-200">
                                    {{ __('No IP reputation data is stored for this account.') }}
                                </flux:callout>
                            @else
                                <div class="mt-4 space-y-3">
                                    @foreach ($ipAddresses as $index => $address)
                                        <div
                                            wire:key="ip-{{ $index }}"
                                            class="rounded-2xl border border-white/10 bg-slate-950/80 p-4 text-sm text-slate-200"
                                        >
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                <div class="font-mono text-sm text-sky-300">
                                                    {{ $address['display'] }}
                                                </div>
                                                <div class="text-xs text-slate-400">
                                                    {{ __('Uses :count • Via sitter :sitter', ['count' => $address['uses'], 'sitter' => $address['via_sitter']]) }}
                                                </div>
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ __('Last seen :time (:diff ago)', ['time' => $address['last_seen'], 'diff' => $address['last_seen_diff']]) }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </flux:container>
</div>
