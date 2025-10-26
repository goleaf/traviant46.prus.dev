@php
    $resourceMeta = [
        'wood' => ['label' => __('Wood'), 'icon' => 'presentation-chart-bar', 'accent' => 'text-emerald-300', 'gradient' => 'from-emerald-400 via-emerald-500 to-teal-500'],
        'clay' => ['label' => __('Clay'), 'icon' => 'cube', 'accent' => 'text-orange-300', 'gradient' => 'from-orange-400 via-amber-400 to-rose-400'],
        'iron' => ['label' => __('Iron'), 'icon' => 'shield-check', 'accent' => 'text-sky-300', 'gradient' => 'from-sky-400 via-cyan-400 to-blue-500'],
        'crop' => ['label' => __('Crop'), 'icon' => 'sparkles', 'accent' => 'text-yellow-300', 'gradient' => 'from-yellow-400 via-lime-400 to-green-400'],
    ];

    $queueEntries = data_get($queueSummary, 'entries', []);
    $generatedAt = $snapshotGeneratedAt
        ? \Illuminate\Support\Carbon::parse($snapshotGeneratedAt)->timezone($timezone)
        : null;
    $updatedLabel = $generatedAt
        ? $generatedAt->diffForHumans()
        : __('moments ago');
    $activeEntry = data_get($queueSummary, 'active_entry');
    $userTimezone = $timezone;
    $cspNonce = app()->has('csp.nonce') ? app('csp.nonce') : null;
@endphp

@once
    @push('scripts')
        <script @if ($cspNonce) nonce="{{ $cspNonce }}" @endif>
            window.createVillageDashboardState = function (config) {
                return {
                    resourceKeys: ['wood', 'clay', 'iron', 'crop'],
                    balances: Object.assign({}, config.balances || {}),
                    production: Object.assign({}, config.production || {}),
                    storage: Object.assign({}, config.storage || {}),
                    lastTickAt: config.lastTickAt || null,
                    snapshotGeneratedAt: config.snapshotGeneratedAt || null,
                    queueEntries: (config.queue || []).map(function (entry) {
                        return Object.assign({}, entry, {
                            initialRemaining: Number(entry.remaining_seconds || 0),
                        });
                    }),
                    startedAtMs: Date.now(),
                    intervalHandle: null,
                    display: {},
                    queueState: {},
                    init() {
                        this.cleanup();
                        this.startedAtMs = Date.now();
                        this.recalculate();
                        this.intervalHandle = setInterval(() => this.recalculate(), 1000);
                    },
                    cleanup() {
                        if (this.intervalHandle) {
                            clearInterval(this.intervalHandle);
                            this.intervalHandle = null;
                        }
                    },
                    recalculate() {
                        const nowMs = Date.now();
                        const tickMs = this.lastTickAt ? Date.parse(this.lastTickAt) : nowMs;
                        const elapsedSeconds = Math.max(0, (nowMs - tickMs) / 1000);
                        const loopSeconds = Math.max(0, (nowMs - this.startedAtMs) / 1000);

                        this.resourceKeys.forEach((key) => {
                            const base = Number(this.balances[key] ?? 0);
                            const perHour = Number(this.production[key] ?? 0);
                            const capacity = Number(this.storage[key] ?? 0);

                            const amount = base + perHour * (elapsedSeconds / 3600);
                            const cappedAmount = capacity > 0 ? Math.min(amount, capacity) : amount;
                            const percent = capacity > 0 && capacity !== Infinity
                                ? Math.min(100, (cappedAmount / capacity) * 100)
                                : 0;

                            this.display[key] = {
                                amount: cappedAmount,
                                perHour,
                                capacity,
                                percent,
                            };
                        });

                        this.queueState = {};
                        this.queueEntries.forEach((entry) => {
                            const totalDuration = this.computeTotalDuration(entry);
                            const remaining = Math.max(0, entry.initialRemaining - loopSeconds);
                            const progress = totalDuration > 0
                                ? Math.min(100, Math.max(0, ((totalDuration - remaining) / totalDuration) * 100))
                                : (entry.is_active ? (remaining === 0 ? 100 : 0) : 0);

                            this.queueState[entry.id] = {
                                remaining,
                                progress,
                            };
                        });
                    },
                    computeTotalDuration(entry) {
                        if (entry.starts_at && entry.completes_at) {
                            const start = Date.parse(entry.starts_at);
                            const end = Date.parse(entry.completes_at);
                            if (! Number.isNaN(start) && ! Number.isNaN(end) && end > start) {
                                return (end - start) / 1000;
                            }
                        }

                        return Number(entry.initialRemaining || 0);
                    },
                    formatAmount(value) {
                        const formatter = new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 });
                        return formatter.format(Math.max(0, value));
                    },
                    formatPerHour(value) {
                        const formatter = new Intl.NumberFormat(undefined, { maximumFractionDigits: 1 });
                        return formatter.format(value);
                    },
                    formatPercent(value) {
                        return `${value.toFixed(1)}%`;
                    },
                    formatDuration(seconds) {
                        const total = Math.max(0, Math.round(seconds));
                        const hours = Math.floor(total / 3600);
                        const minutes = Math.floor((total % 3600) / 60);
                        const secs = total % 60;

                        if (hours > 0) {
                            return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                        }

                        return `${minutes}:${String(secs).padStart(2, '0')}`;
                    },
                    amountFor(key) {
                        return this.display[key]?.amount ?? 0;
                    },
                    perHourFor(key) {
                        return this.display[key]?.perHour ?? 0;
                    },
                    capacityFor(key) {
                        return this.display[key]?.capacity ?? 0;
                    },
                    percentFor(key) {
                        return this.display[key]?.percent ?? 0;
                    },
                    queueRemaining(id) {
                        return this.queueState[id]?.remaining ?? 0;
                    },
                    queueProgress(id) {
                        return this.queueState[id]?.progress ?? 0;
                    },
                };
            };
        </script>
    @endpush
@endonce

<div
    wire:key="game-village-dashboard"
    x-data="createVillageDashboardState({
        balances: @js($balances),
        production: @js($production),
        storage: @js($storage),
        lastTickAt: @js($lastTickAt),
        snapshotGeneratedAt: @js($snapshotGeneratedAt),
        queue: @js($queueEntries),
    })"
    x-init="init()"
    x-on:beforeunload.window="cleanup()"
    x-on:livewire:navigating.window="cleanup()"
    class="space-y-8"
>
    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_40px_120px_-60px_rgba(37,99,235,0.55)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <flux:heading size="lg" class="text-white">
                    {{ __('Resource flow') }}
                </flux:heading>
                <flux:description class="text-slate-400">
                    {{ __('Live production estimates using the most recent tick from the resource collectors.') }}
                </flux:description>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <flux:button wire:click="refreshDashboard" wire:loading.attr="disabled" icon="arrow-path" variant="outline" class="w-full sm:w-auto">
                    {{ __('Force refresh') }}
                </flux:button>
                <span class="text-xs uppercase tracking-wide text-slate-500">
                    {{ __('Updated :time', ['time' => $updatedLabel]) }}
                </span>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($resourceMeta as $key => $meta)
                <div wire:key="resource-counter-{{ $key }}" class="rounded-2xl border border-white/10 bg-white/5 p-5 dark:bg-slate-950/60">
                    <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                        <span>{{ $meta['label'] }}</span>
                        <flux:icon name="{{ $meta['icon'] }}" class="size-5 {{ $meta['accent'] }}" />
                    </div>
                    <div class="mt-4 flex items-baseline gap-1 text-3xl font-semibold text-white">
                        <span x-text="formatAmount(amountFor('{{ $key }}'))"></span>
                        <span class="text-sm font-normal text-slate-400">
                            /
                            <span x-text="formatAmount(capacityFor('{{ $key }}'))"></span>
                        </span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        {{ __('Production /h:') }}
                        <span class="font-mono text-slate-200" x-text="formatPerHour(perHourFor('{{ $key }}'))"></span>
                    </div>
                    <div class="mt-4 h-2 w-full rounded-full bg-white/10">
                        <div
                            class="h-full rounded-full bg-gradient-to-r {{ $meta['gradient'] }}"
                            :style="`width: ${percentFor('{{ $key }}')}%`"
                        ></div>
                    </div>
                    <div class="mt-2 text-right text-xs text-slate-400">
                        <span x-text="formatPercent(percentFor('{{ $key }}'))"></span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_40px_120px_-60px_rgba(251,191,36,0.45)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1">
                <flux:heading size="lg" class="text-white">
                    {{ __('Construction queue') }}
                </flux:heading>
                <flux:description class="text-slate-400">
                    {{ __('Track active upgrades, live ETAs, and queued tasks dispatched to the master builder.') }}
                </flux:description>
            </div>

            <span class="text-xs uppercase tracking-wide text-slate-500">
                @if ($activeEntry && data_get($activeEntry, 'completes_at'))
                    @php
                        $activeCompletion = \Illuminate\Support\Carbon::parse($activeEntry['completes_at'])
                            ->timezone($userTimezone)
                            ->format('H:i:s');
                    @endphp
                    {{ __('Next completion at :time', ['time' => $activeCompletion]) }}
                @else
                    {{ __('No active builds') }}
                @endif
            </span>
        </div>

        @if (empty($queueEntries))
            <flux:callout variant="subtle" icon="information-circle" class="mt-6 text-slate-200">
                {{ __('The master builder is idle. Queue an upgrade to view live progress statistics here.') }}
            </flux:callout>
        @else
            <div class="mt-6 space-y-4">
                @foreach ($queueEntries as $entry)
                    @php
                        $completionTime = data_get($entry, 'completes_at')
                            ? \Illuminate\Support\Carbon::parse($entry['completes_at'])->timezone($userTimezone)->format('H:i:s')
                            : null;
                    @endphp
                    <div
                        wire:key="queue-entry-{{ $entry['id'] }}"
                        class="rounded-2xl border border-white/10 bg-white/5 p-5 dark:bg-slate-950/60"
                    >
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-sm font-semibold text-white">
                                    <flux:badge variant="{{ data_get($entry, 'is_active') ? 'solid' : 'subtle' }}" color="{{ data_get($entry, 'is_active') ? 'sky' : 'slate' }}">
                                        {{ data_get($entry, 'is_active') ? __('Active') : __('Queued') }}
                                    </flux:badge>
                                    <span>{{ data_get($entry, 'building_name') }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs uppercase tracking-wide text-slate-400">
                                    <span>{{ __('Slot :slot', ['slot' => data_get($entry, 'slot')]) }}</span>
                                    <span>{{ __('Lv :current → :target', ['current' => data_get($entry, 'current_level'), 'target' => data_get($entry, 'target_level')]) }}</span>
                                    <span>{{ __('Queue #:order', ['order' => data_get($entry, 'queue_position')]) }}</span>
                                </div>
                            </div>

                            <div class="text-right text-xs text-slate-300">
                                <div class="font-mono text-sm text-amber-300" x-text="formatDuration(queueRemaining({{ (int) $entry['id'] }}))"></div>
                                @if ($completionTime)
                                    <div class="text-slate-400">
                                        {{ __('ETA') }} <span class="font-mono text-sky-200">{{ $completionTime }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 h-2 w-full rounded-full bg-white/10">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-500 transition-[width] duration-300 ease-linear"
                                :style="`width: ${queueProgress({{ (int) $entry['id'] }})}%`"
                            ></div>
                        </div>

                        @if (! empty(data_get($entry, 'segments')))
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                @foreach (data_get($entry, 'segments', []) as $segmentIndex => $segment)
                                    <div wire:key="queue-entry-{{ $entry['id'] }}-segment-{{ $segmentIndex }}" class="rounded-xl border border-white/5 bg-white/5 px-3 py-2 text-xs text-slate-300 dark:bg-slate-900/80">
                                        <div class="flex items-center justify-between">
                                            <span>{{ __('Level :level', ['level' => data_get($segment, 'level')]) }}</span>
                                            <span class="font-mono text-emerald-200">
                                                {{ \Carbon\CarbonInterval::seconds((int) data_get($segment, 'duration'))->cascade()->forHumans(['parts' => 2]) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <div wire:loading.flex class="items-center gap-2 text-sm text-slate-300">
        <flux:icon name="arrow-path" class="size-4 animate-spin text-sky-300" />
        <span>{{ __('Syncing dashboard...') }}</span>
    </div>
</div>
