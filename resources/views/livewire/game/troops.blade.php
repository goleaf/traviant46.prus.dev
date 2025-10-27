@php
    use Illuminate\Support\Carbon;
    use Carbon\CarbonInterval;
    use Illuminate\Support\Str;

    $ownedUnits = data_get($garrison, 'owned', []);
    $reinforcements = data_get($garrison, 'reinforcements', []);
    $totals = [
        'owned' => (int) data_get($garrison, 'totals.owned', 0),
        'reinforcements' => (int) data_get($garrison, 'totals.reinforcements', 0),
        'overall' => (int) data_get($garrison, 'totals.overall', 0),
    ];
    $queueEntries = data_get($queue, 'entries', []);
    $nextCompletion = data_get($queue, 'next_completion_at');
    $userTimezone = optional(auth()->user())->timezone ?? config('app.timezone');
    $canTrain = ! empty($availableUnits);
    /**
     * Calculate queue summary figures so the UI can highlight totals alongside
     * the live timers without recalculating them in the template body.
     */
    $totalQueuedUnits = 0;
    $activeQueueBatches = 0;

    foreach ($queueEntries as $queueEntry) {
        $queuedQuantity = (int) data_get($queueEntry, 'quantity', 0);
        $totalQueuedUnits += $queuedQuantity;

        if (! empty(data_get($queueEntry, 'is_active'))) {
            $activeQueueBatches++;
        }
    }
    $overviewUrl = \Illuminate\Support\Facades\Route::has('game.villages.overview')
        ? route('game.villages.overview', $village)
        : null;
@endphp

<div class="space-y-10">
    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_30px_80px_-50px_rgba(29,78,216,0.55)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <flux:heading level="1" size="xl" class="text-white">
                    {{ __('Troop command centre') }}
                </flux:heading>
                <flux:description class="text-slate-300">
                    {{ __('Review :village’s standing armies, supporting reinforcements, and coordinate fresh training batches.', ['village' => $village->name]) }}
                </flux:description>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <flux:button wire:click="refreshOverview" wire:loading.attr="disabled" icon="arrow-path" variant="primary" color="sky" class="w-full sm:w-auto">
                    {{ __('Refresh data') }}
                </flux:button>
                @if ($overviewUrl)
                    <flux:button as="a" href="{{ $overviewUrl }}" icon="presentation-chart-bar" variant="outline" class="w-full sm:w-auto">
                        {{ __('Back to overview') }}
                    </flux:button>
                @endif
            </div>
        </div>

        @if ($statusMessage)
            <flux:callout variant="success" icon="check-circle" class="mt-6 text-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <span>{{ $statusMessage }}</span>
                    <flux:button wire:click="dismissMessage" wire:loading.attr="disabled" size="xs" variant="ghost" color="emerald">
                        {{ __('Dismiss') }}
                    </flux:button>
                </div>
            </flux:callout>
        @endif

        @if ($errorMessage)
            <flux:callout variant="danger" icon="exclamation-triangle" class="mt-6 text-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <span>{{ $errorMessage }}</span>
                    <flux:button wire:click="dismissMessage" wire:loading.attr="disabled" size="xs" variant="ghost" color="rose">
                        {{ __('Close') }}
                    </flux:button>
                </div>
            </flux:callout>
        @endif

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 shadow-inner dark:bg-slate-950/60">
                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                    <span>{{ __('Village garrison') }}</span>
                    <flux:icon name="shield-check" class="size-5 text-emerald-300" />
                </div>
                <div class="mt-4 text-3xl font-semibold text-white">
                    {{ number_format($totals['owned']) }}
                </div>
                <p class="mt-2 text-xs text-slate-400">
                    {{ __('Standing forces stationed within this village.') }}
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 shadow-inner dark:bg-slate-950/60">
                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                    <span>{{ __('Reinforcements') }}</span>
                    <flux:icon name="users" class="size-5 text-sky-300" />
                </div>
                <div class="mt-4 text-3xl font-semibold text-white">
                    {{ number_format($totals['reinforcements']) }}
                </div>
                <p class="mt-2 text-xs text-slate-400">
                    {{ __('Friendly troops currently supporting your village.') }}
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 shadow-inner dark:bg-slate-950/60">
                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                    <span>{{ __('Total defenders') }}</span>
                    <flux:icon name="globe-alt" class="size-5 text-amber-300" />
                </div>
                <div class="mt-4 text-3xl font-semibold text-white">
                    {{ number_format($totals['overall']) }}
                </div>
                <p class="mt-2 text-xs text-slate-400">
                    {{ __('Combined headcount of stationed and reinforced troops.') }}
                </p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-50px_rgba(16,185,129,0.55)] backdrop-blur dark:bg-slate-950/70">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg" class="text-white">
                        {{ __('Garrison composition') }}
                    </flux:heading>
                    <flux:description class="text-slate-400">
                        {{ __('All units raised and stationed inside :village.', ['village' => $village->name]) }}
                    </flux:description>
                </div>
                <span class="text-xs uppercase tracking-wide text-slate-400">
                    {{ __('Total: :count', ['count' => number_format($totals['owned'])]) }}
                </span>
            </div>

            @if ($ownedUnits === [])
                <flux:callout variant="subtle" icon="military-symbol" class="mt-6 text-slate-200">
                    {{ __('No troops are currently stationed in this village.') }}
                </flux:callout>
            @else
                <div class="mt-6 space-y-3">
                    @foreach ($ownedUnits as $index => $unit)
                        <div wire:key="garrison-owned-{{ data_get($unit, 'unit_type_id', $index) }}" class="flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 px-4 py-3 text-sm text-slate-200 dark:bg-slate-900/80">
                            <div>
                                <div class="font-semibold text-white">
                                    {{ data_get($unit, 'name') }}
                                </div>
                                @if (! empty(data_get($unit, 'code')))
                                    <div class="text-xs uppercase tracking-wide text-slate-400">
                                        {{ Str::headline((string) data_get($unit, 'code')) }}
                                    </div>
                                @endif
                            </div>

                            <div class="text-right">
                                <div class="font-mono text-lg text-sky-200">
                                    {{ number_format((int) data_get($unit, 'quantity', 0)) }}
                                </div>
                                @if (! empty(data_get($unit, 'total_upkeep')))
                                    <div class="text-xs text-slate-400">
                                        {{ __('Upkeep: :value', ['value' => number_format((int) data_get($unit, 'total_upkeep', 0))]) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-50px_rgba(59,130,246,0.55)] backdrop-blur dark:bg-slate-950/70">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg" class="text-white">
                        {{ __('Supporting reinforcements') }}
                    </flux:heading>
                    <flux:description class="text-slate-400">
                        {{ __('Allied commands currently garrisoned here.') }}
                    </flux:description>
                </div>
                <span class="text-xs uppercase tracking-wide text-slate-400">
                    {{ __('Total: :count', ['count' => number_format($totals['reinforcements'])]) }}
                </span>
            </div>

            @if ($reinforcements === [])
                <flux:callout variant="subtle" icon="hand-raised" class="mt-6 text-slate-200">
                    {{ __('No friendly reinforcements are stationed in this village.') }}
                </flux:callout>
            @else
                <div class="mt-6 space-y-4">
                    @foreach ($reinforcements as $index => $reinforcement)
                        <div wire:key="reinforcement-{{ data_get($reinforcement, 'id', $index) }}" class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-200 shadow-inner dark:bg-slate-900/80">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-white">
                                        {{ data_get($reinforcement, 'owner') ?? __('Unknown commander') }}
                                    </div>
                                    <div class="text-xs uppercase tracking-wide text-slate-400">
                                        @if (data_get($reinforcement, 'home_village'))
                                            {{ __('From :village', ['village' => data_get($reinforcement, 'home_village')]) }}
                                        @else
                                            {{ __('Origin undisclosed') }}
                                        @endif
                                    </div>
                                </div>

                                <div class="text-right text-xs text-slate-400">
                                    <div class="font-mono text-lg text-amber-200">
                                        {{ number_format((int) data_get($reinforcement, 'total', 0)) }}
                                    </div>

                                    @if (data_get($reinforcement, 'deployed_at'))
                                        <div>
                                            {{ __('Deployed :time', ['time' => Carbon::parse(data_get($reinforcement, 'deployed_at'))->diffForHumans()]) }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach (data_get($reinforcement, 'units', []) as $unitIndex => $unit)
                                    <span wire:key="reinforcement-{{ data_get($reinforcement, 'id', $index) }}-unit-{{ $unitIndex }}" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs text-slate-100 dark:bg-slate-900/90">
                                        <span class="font-mono text-sky-200">{{ number_format((int) data_get($unit, 'quantity', 0)) }}</span>
                                        <span>{{ data_get($unit, 'name') }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section wire:poll.1500ms.keep-alive="refreshQueue" class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-50px_rgba(236,72,153,0.55)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="lg" class="text-white">
                    {{ __('Training queue') }}
                </flux:heading>
                <flux:description class="text-slate-400">
                    {{ __('Active and pending batches scheduled at your barracks, stable, or workshop.') }}
                </flux:description>
            </div>

            <div class="flex flex-col items-start gap-2 text-xs text-slate-300 sm:items-end sm:text-right">
                <div class="font-mono text-slate-200">
                    {{ __('Queued units: :count', ['count' => number_format($totalQueuedUnits)]) }}
                </div>
                <div class="text-slate-400">
                    {{ __('Active batches: :count', ['count' => number_format($activeQueueBatches)]) }}
                </div>

                @if ($nextCompletion)
                    <div>
                        {{ __('Next completion:') }}
                        <span class="font-mono text-sky-200">
                            {{ Carbon::parse($nextCompletion)->timezone($userTimezone)->toDayDateTimeString() }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        @if ($queueEntries === [])
            <flux:callout variant="subtle" icon="sparkles" class="mt-6 text-slate-200">
                {{ __('No batches are currently queued. Schedule new training below to keep your barracks busy.') }}
            </flux:callout>
        @else
            <div class="mt-6 space-y-4">
                @foreach ($queueEntries as $entryIndex => $entry)
                    <div wire:key="training-entry-{{ data_get($entry, 'id', $entryIndex) }}" class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-200 dark:bg-slate-900/80">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-3">
                                    <flux:badge variant="{{ data_get($entry, 'is_active') ? 'solid' : 'subtle' }}" color="{{ data_get($entry, 'is_active') ? 'emerald' : 'slate' }}">
                                        {{ data_get($entry, 'is_active') ? __('Processing') : __('Queued') }}
                                    </flux:badge>
                                    <span class="font-semibold text-white">
                                        {{ data_get($entry, 'unit_name') }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs uppercase tracking-wide text-slate-400">
                                    <span>{{ __('Position #:pos', ['pos' => data_get($entry, 'queue_position')]) }}</span>
                                    <span>{{ __('Quantity :qty', ['qty' => number_format((int) data_get($entry, 'quantity', 0))]) }}</span>
                                    @if (data_get($entry, 'training_building'))
                                        <span>{{ Str::headline((string) data_get($entry, 'training_building')) }}</span>
                                    @endif
                                </div>
                            </div>

                            @php
                                $remaining = (int) data_get($entry, 'remaining_seconds', 0);
                                $startsAt = data_get($entry, 'starts_at');
                                $completesAt = data_get($entry, 'completes_at');
                            @endphp

                            <div class="text-right text-xs text-slate-300">
                                @if ($remaining > 0)
                                    <div class="font-mono text-sm text-amber-300">
                                        {{ CarbonInterval::seconds($remaining)->cascade()->forHumans(['parts' => 2]) }}
                                    </div>
                                    <div class="text-slate-400">
                                        {{ __('Ready at') }}
                                        <span class="font-mono text-sky-200">
                                            {{ $completesAt ? Carbon::parse($completesAt)->timezone($userTimezone)->format('H:i:s') : '—' }}
                                        </span>
                                    </div>
                                @elseif ($completesAt)
                                    <div class="font-mono text-xs text-emerald-300">
                                        {{ __('Completing shortly') }}
                                    </div>
                                @elseif ($startsAt)
                                    <div class="font-mono text-xs text-slate-400">
                                        {{ __('Starts :time', ['time' => Carbon::parse($startsAt)->diffForHumans()]) }}
                                    </div>
                                @else
                                    <div class="font-mono text-xs text-slate-500">
                                        {{ __('Awaiting slot availability') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div wire:loading.flex wire:target="refreshQueue" class="mt-4 items-center gap-2 text-sm text-slate-300">
            <flux:icon name="arrow-path" class="size-4 animate-spin text-sky-300" />
            <span>{{ __('Syncing training queue...') }}</span>
        </div>
    </section>

    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-50px_rgba(34,197,94,0.55)] backdrop-blur dark:bg-slate-950/70">
        <div class="space-y-1">
            <flux:heading size="lg" class="text-white">
                {{ __('Schedule new training') }}
            </flux:heading>
            <flux:description class="text-slate-400">
                {{ __('Select a troop type, define the batch size, and optionally target a specialised building to expand your forces.') }}
            </flux:description>
        </div>

        <form wire:submit.prevent="train" class="mt-6 space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:field label="{{ __('Troop type') }}" hint="{{ __('Choose which unit to train next.') }}">
                    <flux:select wire:model.live="form.unit_type_id" :disabled="! $canTrain" placeholder="{{ $canTrain ? __('Select troop') : __('No troops available') }}">
                        @foreach ($availableUnits as $unitId => $unit)
                            <flux:select.option value="{{ $unitId }}">
                                {{ $unit['name'] }}
                                <span class="text-xs text-slate-400">
                                    {{ __('· Upkeep :value', ['value' => $unit['upkeep'] ?? '—']) }}
                                </span>
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field label="{{ __('Batch size') }}" hint="{{ __('How many units to add to the queue.') }}">
                    <flux:input
                        type="number"
                        min="1"
                        step="1"
                        wire:model.defer="form.quantity"
                        placeholder="0"
                        :disabled="! $canTrain"
                    />
                </flux:field>
            </div>

            <flux:field label="{{ __('Training building (optional)') }}" hint="{{ __('Target a specific academy, barracks, or workshop.') }}">
                <flux:select wire:model.defer="form.training_building" :disabled="empty($trainingBuildings)">
                    <flux:select.option value="">{{ __('Any available building') }}</flux:select.option>
                    @foreach ($trainingBuildings as $key => $label)
                        <flux:select.option value="{{ $key }}">
                            {{ $label }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-400">
                    {{ __('Training requests are validated against building requirements and queued in chronological order.') }}
                </p>

                <flux:button type="submit" icon="plus-circle" variant="primary" color="emerald" class="w-full sm:w-auto" :disabled="! $canTrain" wire:loading.attr="disabled">
                    {{ __('Train') }}
                </flux:button>
            </div>
        </form>
    </section>
</div>
