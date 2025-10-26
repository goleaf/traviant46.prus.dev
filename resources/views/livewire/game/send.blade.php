@php
    $missionOptions = $this->missionOptions();
    $preview = $preview ?? null;
    $durationDisplay = '';
    $arrivalDisplay = '';

    if ($preview !== null) {
        $seconds = (int) ($preview['duration_seconds'] ?? 0);
        $durationDisplay = $seconds >= 3600
            ? gmdate('H:i:s', $seconds)
            : gmdate('i:s', $seconds);

        $arrivalDisplay = \Illuminate\Support\Carbon::parse($preview['arrive_at'])
            ->tz(auth()->user()?->timezone ?? config('app.timezone'))
            ->toDayDateTimeString();
    }
@endphp

<div class="space-y-8">
    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_40px_120px_-60px_rgba(37,99,235,0.55)] backdrop-blur">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <flux:heading size="lg" class="text-white">
                    {{ __('Dispatch troops') }}
                </flux:heading>
                <flux:description class="text-slate-400">
                    {{ __('Choose units, a mission profile, and coordinates to queue a new rally point movement.') }}
                </flux:description>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 text-sm text-slate-300">
                <div class="font-semibold text-white">{{ $village->name }}</div>
                <div class="mt-1 flex items-center gap-2 text-xs uppercase tracking-wide text-slate-400">
                    <flux:icon name="map-pin" class="size-4 text-sky-300" />
                    <span>{{ __('Origin (:x|:y)', ['x' => $village->x_coordinate, 'y' => $village->y_coordinate]) }}</span>
                </div>
            </div>
        </div>

        @if ($statusMessage)
            <flux:callout class="mt-6 border-emerald-500/50 bg-emerald-900/40 text-emerald-50" icon="check-circle" variant="success">
                {{ $statusMessage }}
            </flux:callout>
        @endif

        <form wire:submit.prevent="submit" class="mt-8 space-y-8">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-4 rounded-2xl border border-white/10 bg-slate-950/60 p-6">
                    <flux:field label="{{ __('Target X coordinate') }}">
                        <flux:input type="number" wire:model.live="targetX" placeholder="0" />
                    </flux:field>
                    @error('targetX')
                        <p class="text-sm text-red-400">{{ $message }}</p>
                    @enderror

                    <flux:field label="{{ __('Target Y coordinate') }}">
                        <flux:input type="number" wire:model.live="targetY" placeholder="0" />
                    </flux:field>
                    @error('targetY')
                        <p class="text-sm text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4 text-sm text-slate-300">
                        <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-slate-400">
                            <flux:icon name="flag" class="size-4 text-amber-300" />
                            <span>{{ __('Destination summary') }}</span>
                        </div>
                        @if ($targetVillage)
                            <div class="mt-3 space-y-1">
                                <div class="font-semibold text-white">{{ $targetVillage['name'] ?: __('Unnamed village') }}</div>
                                @if (! empty($targetVillage['owner']))
                                    <div class="text-xs text-slate-400">
                                        {{ __('Owner: :name', ['name' => $targetVillage['owner']]) }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="mt-3 text-xs text-slate-400">
                                {{ __('Select valid coordinates to resolve the destination village.') }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="space-y-4 rounded-2xl border border-white/10 bg-slate-950/60 p-6">
                    <flux:field label="{{ __('Mission profile') }}">
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($missionOptions as $value => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-slate-200 hover:border-sky-400/60 hover:text-white">
                                    <input
                                        type="radio"
                                        name="mission"
                                        value="{{ $value }}"
                                        wire:model.live="mission"
                                        class="size-4 border-slate-600 bg-slate-950 text-sky-400 focus:ring-sky-400"
                                    />
                                    <span class="font-medium">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </flux:field>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-950/60">
                <div class="border-b border-white/10 px-6 py-4 text-sm uppercase tracking-wide text-slate-400">
                    {{ __('Unit composition') }}
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-left text-sm text-slate-200">
                        <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Unit') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Available') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Send') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($availableUnits as $unit)
                                <tr wire:key="unit-row-{{ $unit['id'] }}" class="bg-slate-950/40">
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-white">{{ $unit['name'] }}</div>
                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ __('Speed: :speed | Upkeep: :upkeep', ['speed' => $unit['speed'] ?? '—', 'upkeep' => $unit['upkeep']]) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 font-mono text-amber-300">
                                        {{ number_format($unit['quantity']) }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <flux:input
                                            type="number"
                                            min="0"
                                            max="{{ $unit['quantity'] }}"
                                            wire:model.live="formUnits.{{ $unit['id'] }}"
                                            class="w-28"
                                        />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-400">
                                        {{ __('No troops ready for dispatch in this village.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @error('formUnits')
                    <p class="px-6 py-3 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-6">
                    <flux:heading size="sm" class="text-white">
                        {{ __('Movement preview') }}
                    </flux:heading>
                    @if ($preview === null)
                        <p class="mt-3 text-sm text-slate-400">
                            {{ __('Adjust coordinates and unit counts to calculate the route and upkeep cost.') }}
                        </p>
                    @else
                        <dl class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('Distance') }}</dt>
                                <dd class="mt-1 text-lg font-semibold text-white">
                                    {{ number_format($preview['distance'], 2) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('Travel time') }}</dt>
                                <dd class="mt-1 text-lg font-semibold text-white">{{ $durationDisplay }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('Crop upkeep') }}</dt>
                                <dd class="mt-1 text-lg font-semibold text-white">{{ number_format($preview['upkeep']) }}</dd>
                            </div>
                        </dl>
                        <div class="mt-5 rounded-xl border border-white/10 bg-slate-900/60 p-4 text-sm text-slate-300">
                            <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-slate-400">
                                <flux:icon name="clock" class="size-4 text-sky-300" />
                                <span>{{ __('Arrival window') }}</span>
                            </div>
                            <div class="mt-2 font-medium text-white">{{ $arrivalDisplay }}</div>
                        </div>
                    @endif
                </div>

                <div class="flex flex-col justify-between gap-4 rounded-2xl border border-white/10 bg-slate-950/60 p-6">
                    <p class="text-sm text-slate-400">
                        {{ __('Sending troops consumes crop upkeep immediately. Double-check orders when acting as a sitter.') }}
                    </p>
                    <flux:button type="submit" icon="paper-airplane" class="w-full justify-center" wire:loading.attr="disabled">
                        {{ __('Queue movement') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </section>
</div>
