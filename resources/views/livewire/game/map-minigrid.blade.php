@php
    $columns = $columns > 0 ? $columns : count($grid[0] ?? []);
@endphp

<section class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-55px_rgba(56,189,248,0.55)] backdrop-blur dark:bg-slate-950/70">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-2">
            <flux:heading size="lg" class="text-white">
                {{ __('Local map minigrid') }}
            </flux:heading>
            <flux:description class="text-slate-400 max-w-2xl">
                {{ __('Scan the surrounding tiles around this village, review terrain bonuses, and hop into troop dispatch without leaving the overview.') }}
            </flux:description>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:field label="{{ __('Radius (tiles)') }}" hint="{{ __('Controls how many rings around the village are included.') }}">
                <flux:select wire:model.live="radius" class="min-w-[7rem]">
                    @foreach ($radiusOptions as $option)
                        <option value="{{ $option }}">
                            {{ $option }} {{ \Illuminate\Support\Str::plural(__('tile'), $option) }}
                        </option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:button
                wire:click="refreshGrid"
                wire:loading.attr="disabled"
                icon="arrow-path"
                variant="outline"
                class="w-full sm:w-auto"
            >
                {{ __('Refresh map') }}
            </flux:button>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3 text-sm text-slate-300">
        <span>{{ __('Centre coordinate') }}:</span>
        <span class="font-mono text-sky-200">
            ({{ $village->x_coordinate }}, {{ $village->y_coordinate }})
        </span>
        <span class="rounded-full bg-slate-800/60 px-3 py-1 text-xs uppercase tracking-wide text-slate-400">
            {{ __('Grid size: :count×:count', ['count' => $radius * 2 + 1]) }}
        </span>
    </div>

    @if (empty($grid))
        <div class="mt-6 rounded-2xl border border-dashed border-white/10 bg-white/5 p-6 text-center text-sm text-slate-400 dark:bg-slate-950/60">
            {{ __('Map data is unavailable for this region.') }}
        </div>
    @else
        <div class="mt-6 space-y-3">
            @foreach ($grid as $rowIndex => $row)
                <div
                    class="grid gap-3"
                    style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));"
                >
                    @foreach ($row as $tile)
                        <div
                            wire:key="tile-{{ $tile['x'] }}-{{ $tile['y'] }}"
                            class="rounded-2xl border border-white/10 bg-white/5 p-3 text-xs text-slate-200 transition hover:border-sky-400/60 hover:bg-slate-800/60 dark:bg-slate-950/60 {{ $tile['is_center'] ? 'ring-1 ring-sky-400' : '' }}"
                        >
                            <div class="flex items-center justify-between text-[11px] uppercase tracking-wide text-slate-400">
                                <span class="font-mono text-xs text-slate-200">({{ $tile['x'] }}, {{ $tile['y'] }})</span>
                                <span class="text-[10px] text-slate-400">
                                    {{ $tile['category_label'] }}
                                </span>
                            </div>

                            <div class="mt-2 text-sm font-semibold text-white">
                                {{ $tile['label'] }}
                            </div>

                            @if (! empty($tile['details']))
                                <div class="mt-1 text-[11px] text-slate-400">
                                    {{ $tile['details'] }}
                                </div>
                            @endif

                            @if (! empty($tile['effects']))
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($tile['effects'] as $effect)
                                        <span class="rounded-full bg-slate-800/60 px-2 py-0.5 text-[10px] text-sky-200">
                                            {{ $effect }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if (! empty($tile['badges']))
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($tile['badges'] as $badge)
                                        <span class="rounded-full border border-white/15 bg-white/10 px-2 py-0.5 text-[10px] text-emerald-200">
                                            {{ $badge }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-400">
                                <span>{{ __('Distance: :distance', ['distance' => $tile['distance']]) }}</span>

                                @if ($sendRouteAvailable)
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        wire:click="openSend({{ $tile['x'] }}, {{ $tile['y'] }})"
                                        wire:loading.attr="disabled"
                                        class="text-sky-200 hover:text-white"
                                    >
                                        {{ __('Send') }}
                                    </flux:button>
                                @else
                                    <span class="text-slate-500">{{ __('Send unavailable') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</section>
