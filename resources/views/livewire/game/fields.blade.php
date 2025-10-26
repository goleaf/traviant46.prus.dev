@php
    $noticeColor = [
        'success' => 'emerald',
        'warning' => 'amber',
        'error' => 'rose',
        'info' => 'sky',
    ][$noticeType ?? 'info'] ?? 'sky';
    $infrastructureUrl = Route::has('game.villages.infrastructure')
        ? route('game.villages.infrastructure', $village)
        : '#';
@endphp

<div class="space-y-10">
    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_30px_80px_-50px_rgba(14,116,144,0.8)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <flux:heading level="1" size="xl" class="text-white">
                    {{ __('Resource fields') }}
                </flux:heading>
                <flux:description class="max-w-2xl text-slate-300">
                    {{ __('Survey your outer ring, line up efficient upgrades, and keep the granaries flowing without tabbing back to the legacy interface.') }}
                </flux:description>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <flux:button wire:click="refreshSnapshots" wire:loading.attr="disabled" icon="arrow-path" variant="primary" color="sky" class="w-full sm:w-auto">
                    {{ __('Refresh fields') }}
                </flux:button>
                <flux:button as="a" href="{{ $infrastructureUrl }}" icon="home-modern" variant="outline" class="w-full sm:w-auto">
                    {{ __('Go to infrastructure') }}
                </flux:button>
            </div>
        </div>

        <div wire:loading.flex class="mt-6 items-center gap-2 text-sm text-slate-300">
            <flux:icon name="arrow-path" class="size-4 animate-spin text-sky-300" />
            <span>{{ __('Syncing resource fields...') }}</span>
        </div>
    </section>

    <section class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-8">
            <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-60px_rgba(56,189,248,0.65)] backdrop-blur dark:bg-slate-950/70">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <flux:heading size="lg" class="text-white">
                        {{ __('Outer resource ring (slots 1–18)') }}
                    </flux:heading>
                    <span class="text-xs uppercase tracking-wide text-slate-400">
                        {{ __('Upgrade durations reflect current main building speed modifiers.') }}
                    </span>
                </div>

                @if ($notice)
                    <flux:callout icon="information-circle" variant="subtle" color="{{ $noticeColor }}" class="mt-6">
                        {{ $notice }}
                    </flux:callout>
                @endif

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($fields as $field)
                        @php
                            $isLocked = (bool) data_get($field, 'is_locked');
                            $queuedLevel = data_get($field, 'queued_level');
                            $queueStatus = data_get($field, 'queue_status');
                            $statusColor = $isLocked ? 'rose' : ($queueStatus ? 'amber' : 'emerald');
                            $statusLabel = $isLocked
                                ? __('Locked')
                                : ($queueStatus ? __('Queued to Lv :level', ['level' => $queuedLevel]) : __('Ready'));
                            $duration = data_get($field, 'duration_formatted');
                            $disableUpgrade = $isLocked || ! empty($queueStatus);
                        @endphp

                        <div
                            wire:key="field-card-{{ data_get($field, 'id') }}"
                            class="relative flex flex-col gap-5 rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:border-sky-400 hover:shadow-[0_16px_40px_-35px_rgba(56,189,248,0.9)] dark:bg-slate-900/70"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-2xl border border-white/10 bg-slate-950/70 text-lg font-semibold text-white">
                                        {{ str_pad((string) data_get($field, 'slot'), 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <div>
                                        <div class="text-sm font-semibold text-white">
                                            {{ data_get($field, 'name') }}
                                        </div>
                                        <div class="text-xs uppercase tracking-wide text-slate-400">
                                            {{ __('Level :level', ['level' => data_get($field, 'level')]) }}
                                        </div>
                                    </div>
                                </div>
                                <flux:badge variant="subtle" color="{{ $statusColor }}">
                                    {{ $statusLabel }}
                                </flux:badge>
                            </div>

                            <div class="space-y-3 text-sm text-slate-300">
                                <div class="flex items-center justify-between">
                                    <span>{{ __('Production /h') }}</span>
                                    <span class="font-mono text-white">
                                        {{ number_format((int) data_get($field, 'production_per_hour')) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>{{ __('Next level') }}</span>
                                    <span class="font-mono text-emerald-300">
                                        {{ data_get($field, 'next_level') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>{{ __('Upgrade time') }}</span>
                                    <span class="font-mono text-slate-200">
                                        {{ $duration ?? '—' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="text-xs uppercase tracking-wide text-slate-400">
                                    @if ($queueStatus)
                                        {{ __('Queue status: :status', ['status' => ucfirst($queueStatus)]) }}
                                    @else
                                        {{ __('No queue entry') }}
                                    @endif
                                </div>
                                <flux:button
                                    size="sm"
                                    color="emerald"
                                    variant="primary"
                                    wire:click="enqueueUpgrade({{ data_get($field, 'id') }})"
                                    wire:target="enqueueUpgrade({{ data_get($field, 'id') }})"
                                    wire:loading.attr="disabled"
                                    :disabled="$disableUpgrade"
                                >
                                    {{ __('Upgrade') }}
                                </flux:button>
                            </div>

                            @if ($isLocked)
                                <div class="space-y-2 text-xs text-rose-300">
                                    @foreach (data_get($field, 'locked_reasons', []) as $reason)
                                        <div class="flex items-start gap-2">
                                            <flux:icon name="no-symbol" class="mt-0.5 size-4 text-rose-300" />
                                            <span>{{ $reason }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-60px_rgba(59,130,246,0.6)] backdrop-blur dark:bg-slate-950/70">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="text-white">
                        {{ __('Construction queue') }}
                    </flux:heading>
                    <flux:button size="sm" variant="ghost" wire:click="$emit('buildingQueue:refresh')" class="text-slate-300">
                        {{ __('Refresh queue') }}
                    </flux:button>
                </div>

                @php
                    $queueEntries = data_get($queue, 'entries', []);
                @endphp

                <ul class="mt-6 space-y-3 text-sm text-slate-200">
                    @forelse ($queueEntries as $entry)
                        <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 dark:bg-slate-900/70">
                            <div class="flex items-center justify-between">
                                <span>{{ data_get($entry, 'building_name') }}</span>
                                <span class="font-mono text-xs uppercase tracking-wide text-slate-400">
                                    {{ __('Lv :current → :target', ['current' => data_get($entry, 'current_level'), 'target' => data_get($entry, 'target_level')]) }}
                                </span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                                <span>{{ __('Slot :slot', ['slot' => data_get($entry, 'slot')]) }}</span>
                                @if (data_get($entry, 'is_active'))
                                    <span class="text-emerald-300">{{ __('Active') }}</span>
                                @else
                                    <span>{{ __('Queue #:pos', ['pos' => data_get($entry, 'queue_position')]) }}</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="rounded-2xl border border-dashed border-white/10 px-4 py-5 text-center text-xs uppercase tracking-wide text-slate-400">
                            {{ __('Nothing queued — enqueue upgrades from the field cards.') }}
                        </li>
                    @endforelse
                </ul>
            </div>

            <livewire:village.building-queue :village="$village" />
        </div>
    </section>
</div>
