@php
    $queueEntries = data_get($queue, 'entries', []);
    $activeEntry = data_get($queue, 'active_entry');
@endphp

<div class="space-y-10">
    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_30px_90px_-60px_rgba(56,189,248,0.65)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <flux:heading level="1" size="xl" class="text-white">
                    {{ __('Village buildings') }}
                </flux:heading>
                <flux:description class="max-w-2xl text-slate-300">
                    {{ __('Review constructed structures, validate prerequisites, and queue new levels without leaving the modern interface.') }}
                </flux:description>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <flux:button wire:click="refreshState" wire:loading.attr="disabled" icon="arrow-path" variant="primary" color="sky" class="w-full sm:w-auto">
                    {{ __('Refresh buildings') }}
                </flux:button>
                @if (Route::has('game.villages.infrastructure'))
                    <flux:button as="a" href="{{ route('game.villages.infrastructure', $village) }}" icon="home-modern" variant="outline" class="w-full sm:w-auto">
                        {{ __('Infrastructure map') }}
                    </flux:button>
                @endif
                @if (Route::has('game.villages.overview'))
                    <flux:button as="a" href="{{ route('game.villages.overview', $village) }}" icon="presentation-chart-bar" variant="ghost" class="w-full sm:w-auto text-slate-200 hover:text-white">
                        {{ __('Resource overview') }}
                    </flux:button>
                @endif
            </div>
        </div>

        @if ($activeEntry)
            <div class="mt-8 rounded-2xl border border-sky-500/40 bg-sky-500/10 p-4 text-sm text-sky-100">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <span class="font-semibold">
                        {{ __('Currently upgrading: :name', ['name' => data_get($activeEntry, 'building_name')]) }}
                    </span>
                    <span class="font-mono text-xs uppercase tracking-wide text-sky-200">
                        {{ __('Lv :current → :target', ['current' => data_get($activeEntry, 'current_level'), 'target' => data_get($activeEntry, 'target_level')]) }}
                    </span>
                </div>
                <div class="mt-1 text-xs">
                    {{ __('Expected completion: :time', ['time' => optional(data_get($activeEntry, 'completes_at')) ? \Illuminate\Support\Carbon::parse(data_get($activeEntry, 'completes_at'))->timezone(optional(auth()->user())->timezone ?? config('app.timezone'))->format('H:i:s') : '—']) }}
                </div>
            </div>
        @endif
    </section>

    @if ($flashMessage !== null)
        @php
            $flashVariant = match (data_get($flashMessage, 'type')) {
                'success' => 'success',
                'error' => 'danger',
                'info' => 'neutral',
                default => 'neutral',
            };
        @endphp

        <flux:callout variant="{{ $flashVariant }}" icon="sparkles" class="rounded-3xl border border-white/10 bg-white/5 p-6 text-sm text-slate-200 dark:bg-slate-950/70">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <span>{{ data_get($flashMessage, 'message') }}</span>
                <flux:button wire:click="dismissFlash" size="xs" variant="ghost" icon="x-mark" class="text-slate-300 hover:text-white">
                    {{ __('Dismiss') }}
                </flux:button>
            </div>
        </flux:callout>
    @endif

    <section class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            @forelse ($buildings as $building)
                @php
                    $buildingId = (int) data_get($building, 'id');
                    $canUpgrade = (bool) data_get($building, 'can_upgrade', false);
                    $queuedTarget = data_get($building, 'queued_target_level');
                    $currentLevel = (int) data_get($building, 'level', 0);
                    $effectiveLevel = (int) data_get($building, 'effective_level', $currentLevel);
                    $nextLevel = data_get($building, 'next_level');
                    $nextLevelBonus = data_get($building, 'next_level_bonus');
                    $prerequisites = data_get($building, 'prerequisites', []);
                    $disabledReason = data_get($building, 'disabled_reason');
                @endphp

                <div
                    wire:key="building-card-{{ $buildingId }}"
                    class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-60px_rgba(14,165,233,0.55)] backdrop-blur transition hover:border-sky-400 hover:shadow-[0_25px_70px_-45px_rgba(56,189,248,0.65)] dark:bg-slate-950/70"
                >
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">
                                {{ __('Slot :slot', ['slot' => data_get($building, 'slot')]) }}
                            </p>
                            <div class="mt-1 text-xl font-semibold text-white">
                                {{ data_get($building, 'name') }}
                            </div>
                        </div>

                        <div class="text-right">
                            <p class="text-xs uppercase tracking-wide text-slate-400">
                                {{ __('Effective level') }}
                            </p>
                            <div class="mt-1 text-2xl font-mono text-white">
                                {{ $effectiveLevel }}
                            </div>
                            <flux:badge variant="subtle" color="{{ $queuedTarget && $queuedTarget > $currentLevel ? 'sky' : 'slate' }}" class="mt-2">
                                @if ($queuedTarget && $queuedTarget > $currentLevel)
                                    {{ __('Queued to level :level', ['level' => $queuedTarget]) }}
                                @else
                                    {{ __('Current level :level', ['level' => $currentLevel]) }}
                                @endif
                            </flux:badge>
                        </div>
                    </div>

                    @if ($nextLevelBonus)
                        <div class="mt-4 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-100">
                            {{ $nextLevelBonus }}
                        </div>
                    @endif

                    <div class="mt-5 space-y-3">
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            {{ __('Prerequisites') }}
                        </p>
                        <ul class="space-y-2">
                            @forelse ($prerequisites as $index => $prerequisite)
                                @php
                                    $met = (bool) data_get($prerequisite, 'met', false);
                                @endphp
                                <li
                                    wire:key="building-{{ $buildingId }}-prereq-{{ $index }}"
                                    class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-3 py-2 text-sm dark:bg-slate-900/70"
                                >
                                    <flux:icon name="{{ $met ? 'check-circle' : 'exclamation-circle' }}" class="size-4 {{ $met ? 'text-emerald-400' : 'text-rose-400' }}" />
                                    <span class="text-slate-200">
                                        {{ data_get($prerequisite, 'name') }}
                                    </span>
                                    <span class="ml-auto font-mono text-xs {{ $met ? 'text-emerald-300' : 'text-rose-300' }}">
                                        {{ data_get($prerequisite, 'current_level') }} / {{ data_get($prerequisite, 'required_level') }}
                                    </span>
                                </li>
                            @empty
                                <li class="rounded-2xl border border-dashed border-white/10 px-3 py-3 text-xs text-slate-400">
                                    {{ __('No prerequisites required.') }}
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-400">
                            @if (! $canUpgrade && $disabledReason)
                                {{ $disabledReason }}
                            @elseif ($nextLevel)
                                {{ __('Next target level: :level', ['level' => $nextLevel]) }}
                            @else
                                {{ __('Maximum level reached.') }}
                            @endif
                        </p>

                        <div class="flex items-center gap-3">
                            @if ($canUpgrade)
                                <flux:button
                                    wire:click="upgrade({{ $buildingId }})"
                                    wire:target="upgrade({{ $buildingId }})"
                                    wire:loading.attr="disabled"
                                    variant="primary"
                                    color="violet"
                                    class="w-full sm:w-auto"
                                >
                                    <div class="flex items-center justify-center gap-2">
                                        <span>{{ __('Upgrade') }}</span>
                                        <flux:icon name="arrow-up" class="size-4" />
                                    </div>
                                </flux:button>
                            @else
                                <flux:button
                                    variant="primary"
                                    color="violet"
                                    class="w-full sm:w-auto opacity-60"
                                    disabled
                                >
                                    <div class="flex items-center justify-center gap-2">
                                        <span>{{ __('Upgrade') }}</span>
                                        <flux:icon name="arrow-up" class="size-4" />
                                    </div>
                                </flux:button>
                            @endif

                            <flux:icon
                                wire:loading
                                wire:target="upgrade({{ $buildingId }})"
                                name="arrow-path"
                                class="size-5 animate-spin text-violet-300"
                            />
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-white/10 bg-white/5 p-12 text-center text-sm text-slate-300 dark:bg-slate-950/70">
                    {{ __('No buildings have been constructed yet.') }}
                </div>
            @endforelse
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-60px_rgba(59,130,246,0.55)] backdrop-blur dark:bg-slate-950/70">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="text-white">
                        {{ __('Queue summary') }}
                    </flux:heading>
                    <flux:button size="sm" variant="ghost" wire:click="refreshState" class="text-slate-300">
                        {{ __('Refresh') }}
                    </flux:button>
                </div>

                <ul class="mt-6 space-y-3 text-sm text-slate-200">
                    @forelse ($queueEntries as $entry)
                        <li wire:key="queue-summary-{{ data_get($entry, 'id') }}" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 dark:bg-slate-900/80">
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
                            {{ __('No construction orders queued.') }}
                        </li>
                    @endforelse
                </ul>
            </div>

            <livewire:village.building-queue :village="$village" />
        </div>
    </section>
</div>
