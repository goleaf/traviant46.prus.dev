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

                {{-- Render each resource field as a condensed textual row to match the Travian overlay specification. --}}
                <div class="mt-6 space-y-3">
                    @foreach ($fields as $field)
                        @php
                            $isLocked = (bool) data_get($field, 'is_locked');
                            $queueStatus = data_get($field, 'queue_status');
                            $duration = data_get($field, 'duration_formatted');
                            $disableUpgrade = $isLocked || ! empty($queueStatus);
                        @endphp

                        <div
                            wire:key="field-row-{{ data_get($field, 'id') }}"
                            class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-100 shadow-sm transition hover:border-sky-400 dark:bg-slate-900/70"
                        >
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="font-semibold text-white">
                                    {{ data_get($field, 'name') }} {{ __('L:level', ['level' => data_get($field, 'level')]) }}
                                </span>
                                <span class="text-slate-400">&rarr;</span>
                                <flux:button
                                    size="xs"
                                    color="emerald"
                                    variant="primary"
                                    wire:click="enqueueUpgrade({{ data_get($field, 'id') }})"
                                    wire:target="enqueueUpgrade({{ data_get($field, 'id') }})"
                                    wire:loading.attr="disabled"
                                    :disabled="$disableUpgrade"
                                >
                                    {{ __('[Upgrade]') }}
                                </flux:button>
                                <span class="font-mono text-xs text-slate-300">
                                    {{ $duration ? "({$duration})" : __('(—)') }}
                                </span>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-300">
                                @if ($queueStatus)
                                    <span>{{ __('Queue: :status', ['status' => ucfirst($queueStatus)]) }}</span>
                                @else
                                    <span>{{ __('Ready to enqueue') }}</span>
                                @endif
                                <span class="text-slate-500">{{ __('Slot #:slot', ['slot' => data_get($field, 'slot')]) }}</span>
                                <span class="text-emerald-300">{{ __('Next → :next', ['next' => data_get($field, 'next_level')]) }}</span>
                                <span class="text-sky-300">{{ __('Production/h :amount', ['amount' => number_format((int) data_get($field, 'production_per_hour'))]) }}</span>
                            </div>

                            @if ($isLocked)
                                {{-- When a field is locked we surface the translated reasons to mirror legacy Travian behaviour. --}}
                                <ul class="mt-2 space-y-1 text-xs text-rose-300">
                                    @foreach (data_get($field, 'locked_reasons', []) as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
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
                            {{ __('Nothing queued — enqueue upgrades from the field rows.') }}
                        </li>
                    @endforelse
                </ul>
            </div>

            <livewire:village.building-queue :village="$village" />
        </div>
    </section>
</div>
