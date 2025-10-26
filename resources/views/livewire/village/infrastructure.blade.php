@php
    $queueEntries = data_get($queue, 'entries', []);
@endphp

<div class="space-y-10">
    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_30px_90px_-60px_rgba(30,64,175,0.7)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <flux:heading level="1" size="xl" class="text-white">
                    {{ __('Village infrastructure') }}
                </flux:heading>
                <flux:description class="max-w-2xl text-slate-300">
                    {{ __('Review inner village slots, track upgrade queues, and keep your master builder humming without digging through the legacy interface.') }}
                </flux:description>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <flux:button wire:click="refreshInfrastructure" wire:loading.attr="disabled" icon="arrow-path" variant="primary" color="violet" class="w-full sm:w-auto">
                    {{ __('Refresh infrastructure') }}
                </flux:button>
                <flux:button as="a" href="{{ route('game.villages.overview', $village) }}" variant="outline" icon="squares-2x2" class="w-full sm:w-auto">
                    {{ __('Back to resources') }}
                </flux:button>
            </div>
        </div>

        <div wire:loading.flex class="mt-6 items-center gap-2 text-sm text-slate-300">
            <flux:icon name="arrow-path" class="size-4 animate-spin text-violet-300" />
            <span>{{ __('Syncing building data...') }}</span>
        </div>
    </section>

    <section class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-8">
            <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-60px_rgba(129,140,248,0.6)] backdrop-blur dark:bg-slate-950/70">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <flux:heading size="lg" class="text-white">
                        {{ __('Construction slots 19–40') }}
                    </flux:heading>
                    <span class="text-xs uppercase tracking-wide text-slate-400">
                        {{ __('Queued targets are highlighted with active glow') }}
                    </span>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($slots as $slot)
                        @php
                            $isQueued = (bool) data_get($slot, 'queued_level');
                            $isActive = (bool) data_get($slot, 'is_active');
                            $badgeColor = $isActive ? 'sky' : ($isQueued ? 'amber' : 'slate');
                            $statusLabel = $isActive ? __('Upgrading') : ($isQueued ? __('Queued') : (data_get($slot, 'has_structure') ? __('Idle') : __('Empty')));
                        @endphp

                        <div
                            wire:key="slot-card-{{ data_get($slot, 'slot') }}"
                            class="relative rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:border-sky-400 hover:shadow-[0_10px_35px_-25px_rgba(56,189,248,0.9)] dark:bg-slate-900/70"
                        >
                            @if ($isActive)
                                <span class="absolute -inset-0.5 rounded-2xl bg-sky-500/20 blur-lg"></span>
                            @endif

                            <div class="relative flex flex-col gap-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 place-items-center rounded-2xl border border-white/10 bg-slate-950/70 text-lg font-semibold text-white">
                                            {{ data_get($slot, 'slot') }}
                                        </span>
                                        <div>
                                            <div class="text-sm font-semibold text-white">
                                                {{ data_get($slot, 'name') }}
                                            </div>
                                            <div class="text-xs uppercase tracking-wide text-slate-400">
                                                {{ __('Level :level', ['level' => data_get($slot, 'level')]) }}
                                            </div>
                                        </div>
                                    </div>
                                    <flux:badge variant="subtle" color="{{ $badgeColor }}">
                                        {{ $statusLabel }}
                                    </flux:badge>
                                </div>

                                <div class="space-y-2 text-xs text-slate-300">
                                    <div class="flex items-center justify-between">
                                        <span>{{ __('Current level') }}</span>
                                        <span class="font-mono text-sm text-white">
                                            {{ data_get($slot, 'level') }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>{{ __('Target level') }}</span>
                                        <span class="font-mono text-sm {{ $isQueued ? 'text-emerald-300' : 'text-slate-500' }}">
                                            {{ data_get($slot, 'queued_level') ?? '—' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>{{ __('Queue status') }}</span>
                                        <span class="font-mono text-sm text-slate-400">
                                            {{ data_get($slot, 'queue_status') ?? __('None') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-60px_rgba(59,130,246,0.6)] backdrop-blur dark:bg-slate-950/70">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="text-white">
                        {{ __('Queue summary') }}
                    </flux:heading>
                    <flux:button size="sm" variant="ghost" wire:click="$emit('buildingQueue:refresh')" class="text-slate-300">
                        {{ __('Refresh queue') }}
                    </flux:button>
                </div>

                <ul class="mt-6 space-y-3 text-sm text-slate-200">
                    @forelse ($queueEntries as $entry)
                        <li wire:key="queue-pill-{{ data_get($entry, 'id') }}" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 dark:bg-slate-900/70">
                            <div class="flex items-center justify-between">
                                <span>{{ data_get($entry, 'building_name') }}</span>
                                <span class="font-mono text-xs uppercase tracking-wide text-slate-400">
                                    {{ __('Lv :current → :target', ['current' => data_get($entry, 'current_level'), 'target' => data_get($entry, 'target_level')]) }}
                                </span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                                <span>{{ __('Slot :slot', ['slot' => data_get($entry, 'slot')]) }}</span>
                                <span>
                                    @if (data_get($entry, 'is_active'))
                                        <span class="text-emerald-300">{{ __('Active') }}</span>
                                    @else
                                        {{ __('Queue #:pos', ['pos' => data_get($entry, 'queue_position')]) }}
                                    @endif
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="rounded-2xl border border-dashed border-white/10 px-4 py-5 text-center text-xs uppercase tracking-wide text-slate-400">
                            {{ __('Nothing queued — enqueue upgrades from the slot cards.') }}
                        </li>
                    @endforelse
                </ul>
            </div>

            <livewire:village.building-queue :village="$village" />
        </div>
    </section>
</div>
