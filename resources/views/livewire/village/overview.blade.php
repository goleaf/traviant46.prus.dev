@php
    $production = data_get($resources, 'production', []);
    $baseProduction = data_get($resources, 'base_production', []);
    $fieldProduction = data_get($resources, 'field_production', []);
    $percentBonuses = data_get($resources, 'bonuses.percent', []);
    $flatBonuses = data_get($resources, 'bonuses.flat', []);
    $storage = data_get($resources, 'storage', []);
    $generatedAt = data_get($resources, 'generated_at');

    $resourceMeta = [
        'wood' => ['label' => __('Wood'), 'icon' => 'presentation-chart-bar', 'accent' => 'text-emerald-300', 'bg' => 'bg-emerald-500/10'],
        'clay' => ['label' => __('Clay'), 'icon' => 'archive-box', 'accent' => 'text-orange-300', 'bg' => 'bg-orange-500/10'],
        'iron' => ['label' => __('Iron'), 'icon' => 'shield-check', 'accent' => 'text-sky-300', 'bg' => 'bg-sky-500/10'],
        'crop' => ['label' => __('Crop'), 'icon' => 'sparkles', 'accent' => 'text-yellow-300', 'bg' => 'bg-yellow-500/10'],
    ];

    $generatedLabel = $generatedAt
        ? \Illuminate\Support\Carbon::parse($generatedAt)->diffForHumans()
        : __('moments ago');
@endphp

<div class="space-y-10">
    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_30px_80px_-50px_rgba(15,23,42,0.8)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <flux:heading level="1" size="xl" class="text-white">
                    {{ $village->name }}
                </flux:heading>
                <div class="flex flex-wrap items-center gap-3 text-sm text-slate-300">
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 font-semibold text-slate-200 dark:bg-slate-900/80">
                        {{ __('Coordinates') }}:
                        <span class="font-mono text-sky-200">({{ $village->x_coordinate }}, {{ $village->y_coordinate }})</span>
                    </span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs uppercase tracking-wide text-slate-400 dark:bg-slate-900/80">
                        {{ __('Updated :time', ['time' => $generatedLabel]) }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <flux:button wire:click="refreshSnapshots" wire:loading.attr="disabled" icon="arrow-path" variant="primary" color="sky" class="w-full sm:w-auto">
                    {{ __('Refresh overview') }}
                </flux:button>
                <flux:button as="a" href="{{ route('game.villages.infrastructure', $village) }}" icon="home-modern" variant="outline" class="w-full sm:w-auto">
                    {{ __('Go to infrastructure') }}
                </flux:button>
            </div>
        </div>

        <div class="mt-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($resourceMeta as $key => $meta)
                <div wire:key="resource-card-{{ $key }}" class="rounded-2xl border border-white/10 bg-white/5 p-5 dark:bg-slate-950/60">
                    <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400">
                        <span>{{ $meta['label'] }}</span>
                        <flux:icon name="{{ $meta['icon'] }}" class="size-5 {{ $meta['accent'] }}" />
                    </div>
                    <div class="mt-4 text-2xl font-semibold text-white">
                        {{ number_format((float) ($production[$key] ?? 0), 1) }}
                        <span class="text-xs font-normal text-slate-400">{{ __('per hour') }}</span>
                    </div>
                    <div class="mt-4 grid gap-2 text-sm text-slate-300">
                        <div class="flex items-center justify-between">
                            <span>{{ __('Base & fields') }}</span>
                            <span class="font-mono text-slate-200">
                                {{ number_format((float) ($baseProduction[$key] ?? 0) + (float) ($fieldProduction[$key] ?? 0), 1) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>{{ __('Bonuses') }}</span>
                            <span class="font-mono text-emerald-300">
                                +{{ number_format((float) ($flatBonuses[$key] ?? 0) + ((float) ($percentBonuses[$key] ?? 0) / 100) * ((float) ($baseProduction[$key] ?? 0) + (float) ($fieldProduction[$key] ?? 0)), 1) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>{{ __('Storage cap') }}</span>
                            <span class="font-mono text-slate-300">
                                {{ number_format((float) ($storage[$key] ?? 0)) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div wire:loading.flex class="mt-6 items-center gap-2 text-sm text-slate-300">
            <flux:icon name="arrow-path" class="size-4 animate-spin text-sky-300" />
            <span>{{ __('Refreshing production data...') }}</span>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-50px_rgba(56,189,248,0.6)] backdrop-blur dark:bg-slate-950/70 lg:col-span-2">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <flux:heading size="lg" class="text-white">
                    {{ __('Production breakdown') }}
                </flux:heading>
                <span class="text-xs uppercase tracking-wide text-slate-400">
                    {{ __('Includes field output, buildings, and active boosts') }}
                </span>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl border border-white/10">
                <table class="min-w-full divide-y divide-white/10 text-left text-sm text-slate-200">
                    <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Resource') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Base') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Fields') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Flat bonus') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('% bonus') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Final /h') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($resourceMeta as $key => $meta)
                            @php
                                $base = (float) ($baseProduction[$key] ?? 0);
                                $fields = (float) ($fieldProduction[$key] ?? 0);
                                $flat = (float) ($flatBonuses[$key] ?? 0);
                                $percent = (float) ($percentBonuses[$key] ?? 0);
                                $total = (float) ($production[$key] ?? 0);
                            @endphp

                            <tr wire:key="resource-row-{{ $key }}" class="bg-slate-950/40 hover:bg-slate-900/60">
                                <td class="px-4 py-4 font-medium text-white">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 place-items-center rounded-2xl border border-white/10 {{ $meta['bg'] }}">
                                            <flux:icon name="{{ $meta['icon'] }}" class="size-5 {{ $meta['accent'] }}" />
                                        </span>
                                        <span>{{ $meta['label'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-mono text-sm text-slate-300">
                                    {{ number_format($base, 1) }}
                                </td>
                                <td class="px-4 py-4 font-mono text-sm text-slate-300">
                                    {{ number_format($fields, 1) }}
                                </td>
                                <td class="px-4 py-4 font-mono text-sm text-emerald-300">
                                    +{{ number_format($flat, 1) }}
                                </td>
                                <td class="px-4 py-4 font-mono text-sm text-sky-300">
                                    +{{ number_format($percent, 2) }}%
                                </td>
                                <td class="px-4 py-4 font-mono text-sm text-white">
                                    {{ number_format($total, 1) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-50px_rgba(236,72,153,0.5)] backdrop-blur dark:bg-slate-950/70">
            <div class="space-y-2">
                <flux:heading size="lg" class="text-white">
                    {{ __('Storage health') }}
                </flux:heading>
                <flux:description class="text-slate-400">
                    {{ __('Monitor warehouse and granary pressure to plan overflow mitigations.') }}
                </flux:description>
            </div>

            <div class="mt-6 space-y-4">
                @foreach ($resourceMeta as $key => $meta)
                    @php
                        $capacity = (float) ($storage[$key] ?? 0);
                        $hourly = (float) ($production[$key] ?? 0);
                        $hoursToFull = $capacity > 0 && $hourly > 0 ? $capacity / $hourly : null;
                    @endphp

                    <div wire:key="storage-row-{{ $key }}" class="rounded-2xl border border-white/10 bg-white/5 p-4 dark:bg-slate-950/60">
                        <div class="flex items-center justify-between text-sm font-semibold text-white">
                            <span>{{ $meta['label'] }}</span>
                            <span class="font-mono text-xs uppercase tracking-wide text-slate-400">
                                {{ __('Cap:') }} {{ number_format($capacity) }}
                            </span>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                            <span>{{ __('Production /h') }}</span>
                            <span class="font-mono text-slate-200">{{ number_format($hourly, 1) }}</span>
                        </div>
                        <div class="mt-3 h-2 rounded-full bg-white/10">
                            @php
                                $fillPercentage = $hoursToFull ? min(100, max(0, 100 / max($hoursToFull, 1))) : 0;
                            @endphp
                            <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-blue-500" style="width: {{ $fillPercentage }}%"></div>
                        </div>
                        <div class="mt-3 text-xs text-slate-400">
                            @if ($hoursToFull)
                                {{ __('~:hours hours until full', ['hours' => number_format($hoursToFull, 1)]) }}
                            @else
                                {{ __('No production pressure detected.') }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-50px_rgba(251,191,36,0.5)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="lg" class="text-white">
                    {{ __('Construction queue') }}
                </flux:heading>
                <flux:description class="text-slate-400">
                    {{ __('Stay aligned with master builder timings and finish-now opportunities.') }}
                </flux:description>
            </div>
            <flux:button wire:click="$emit('buildingQueue:refresh')" variant="outline" class="w-full md:w-auto">
                {{ __('Refresh queue widget') }}
            </flux:button>
        </div>

        <div class="mt-6">
            <livewire:village.building-queue :village="$village" />
        </div>
    </section>
</div>
