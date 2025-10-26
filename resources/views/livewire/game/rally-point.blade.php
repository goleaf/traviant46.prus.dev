@php
    $timezone = $timezone ?? config('app.timezone');
@endphp

<div
    wire:key="game-rally-point"
    wire:poll.keep-alive.7s="refreshMovements"
    class="space-y-10"
>
    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_35px_110px_-60px_rgba(56,189,248,0.65)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <flux:icon name="command-line" class="size-7 text-sky-300" />
                    <flux:heading level="1" size="xl" class="text-white">
                        {{ __('Rally point movements') }}
                    </flux:heading>
                </div>
                <flux:description class="max-w-3xl text-slate-300">
                    {{ __('Track every dispatch currently tied to this village. Movements refresh automatically when new orders launch or troops arrive, and the countdown timers keep you on top of return windows.') }}
                </flux:description>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <flux:button
                    wire:click="refreshMovements"
                    wire:loading.attr="disabled"
                    icon="arrow-path"
                    variant="primary"
                    color="sky"
                    class="w-full sm:w-auto"
                >
                    {{ __('Refresh movements') }}
                </flux:button>
                <flux:button
                    as="a"
                    href="{{ route('game.villages.overview', $village) }}"
                    icon="squares-2x2"
                    variant="outline"
                    class="w-full sm:w-auto"
                >
                    {{ __('Resources') }}
                </flux:button>
                <flux:button
                    as="a"
                    href="{{ route('game.villages.infrastructure', $village) }}"
                    icon="home-modern"
                    variant="ghost"
                    class="w-full sm:w-auto text-slate-200 hover:text-white"
                >
                    {{ __('Infrastructure') }}
                </flux:button>
            </div>
        </div>

        <div wire:loading.flex class="mt-4 items-center gap-2 text-sm text-slate-300">
            <flux:icon name="arrow-path" class="size-4 animate-spin text-sky-300" />
            <span>{{ __('Syncing rally point data...') }}</span>
        </div>
    </section>

    @error('movements')
        <flux:callout variant="warning" icon="exclamation-triangle" class="border-amber-500/20 bg-amber-500/10 text-amber-100">
            {{ $message }}
        </flux:callout>
    @enderror

    <section class="grid gap-8 lg:grid-cols-2">
        <div class="space-y-5 rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_30px_90px_-60px_rgba(96,165,250,0.55)] backdrop-blur dark:bg-slate-950/70">
            <div class="flex items-center justify-between">
                <flux:heading size="lg" class="text-white">
                    {{ __('Outgoing commands') }}
                </flux:heading>
                <span class="text-xs uppercase tracking-wide text-slate-400">
                    {{ trans_choice(':count movement|:count movements', count($outgoing)) }}
                </span>
            </div>

            @forelse ($outgoing as $movement)
                @php
                    $coordinates = data_get($movement, 'coordinates');
                    $arrivalIso = $movement['return_at'] ?? $movement['arrive_at'];
                    $arrivalAt = $arrivalIso ? \Illuminate\Support\Carbon::parse($arrivalIso)->timezone($timezone) : null;
                    $launchAt = $movement['depart_at'] ? \Illuminate\Support\Carbon::parse($movement['depart_at'])->timezone($timezone) : null;
                    $remainingSeconds = $movement['remaining_seconds'];
                @endphp

                <div
                    wire:key="outgoing-{{ $movement['id'] }}"
                    class="rounded-2xl border border-white/10 bg-white/5 p-5 shadow-[0_10px_35px_-30px_rgba(56,189,248,0.9)] transition hover:border-sky-400 dark:bg-slate-950/60"
                >
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <flux:badge variant="{{ $movement['status_variant'] }}" color="{{ $movement['status_color'] }}">
                                    {{ $movement['status_label'] }}
                                </flux:badge>
                                <span class="text-sm font-semibold text-white">
                                    {{ \Illuminate\Support\Str::headline((string) $movement['movement_type']) }}
                                </span>
                                @if ($movement['mission'])
                                    <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs uppercase tracking-wide text-slate-400">
                                        {{ \Illuminate\Support\Str::headline((string) $movement['mission']) }}
                                    </span>
                                @endif
                            </div>
                            <div class="space-y-1 text-xs text-slate-300">
                                <div>
                                    <span class="uppercase tracking-wide text-slate-400">{{ __('Destination') }}:</span>
                                    <span class="font-semibold text-white">
                                        {{ $movement['village_name'] ?? __('Unknown village') }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="uppercase tracking-wide text-slate-400">{{ __('Owner') }}:</span>
                                    <span class="font-mono text-sky-200">
                                        {{ $movement['owner_name'] ?? __('Unknown') }}
                                    </span>
                                </div>
                                @if (is_array($coordinates))
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="uppercase tracking-wide text-slate-400">{{ __('Coordinates') }}:</span>
                                        <span class="font-mono text-emerald-300">
                                            ({{ data_get($coordinates, 'x', '?') }}, {{ data_get($coordinates, 'y', '?') }})
                                        </span>
                                    </div>
                                @endif
                                @if ($launchAt)
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="uppercase tracking-wide text-slate-400">{{ __('Launched') }}:</span>
                                        <span class="font-mono text-slate-200">
                                            {{ $launchAt->format('H:i:s') }} {{ $launchAt->format('T') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2 text-right text-xs text-slate-300">
                            @if ($remainingSeconds !== null)
                                <div
                                    x-data="{
                                        seconds: {{ $remainingSeconds }},
                                        display: '',
                                        timer: null,
                                        arrivedLabel: @js(__('Arrived')),
                                        start() {
                                            this.stop();
                                            this.tick();
                                            this.timer = setInterval(() => this.tick(), 1000);
                                        },
                                        stop() {
                                            if (this.timer) {
                                                clearInterval(this.timer);
                                                this.timer = null;
                                            }
                                        },
                                        tick() {
                                            if (this.seconds <= 0) {
                                                this.display = this.arrivedLabel;
                                                this.stop();
                                                return;
                                            }

                                            this.display = this.format(this.seconds);
                                            this.seconds = Math.max(0, this.seconds - 1);
                                        },
                                        format(value) {
                                            const total = Math.max(0, Math.floor(value));
                                            const hours = Math.floor(total / 3600);
                                            const minutes = Math.floor((total % 3600) / 60);
                                            const seconds = total % 60;

                                            if (hours > 0) {
                                                return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                                            }

                                            return `${minutes}:${String(seconds).padStart(2, '0')}`;
                                        }
                                    }"
                                    x-init="start()"
                                    x-on:beforeunload.window="stop()"
                                    x-on:livewire:navigating.window="stop()"
                                    class="font-mono text-base text-amber-200"
                                >
                                    <span x-text="display"></span>
                                </div>
                            @else
                                <span class="font-mono text-base text-slate-500">—</span>
                            @endif

                            @if ($arrivalAt)
                                <div>
                                    <span class="uppercase tracking-wide text-slate-400">{{ __('ETA') }}:</span>
                                    <span class="font-mono text-sky-200">
                                        {{ $arrivalAt->format('H:i:s') }} {{ $arrivalAt->format('T') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-300">
                            {{ __('Keep an eye on the countdown—cancellations are only available while troops are still marching out.') }}
                        </p>

                        @if ($movement['can_cancel'])
                            <flux:button
                                wire:click="cancelMovement({{ $movement['id'] }})"
                                wire:loading.attr="disabled"
                                icon="x-mark"
                                size="sm"
                                variant="outline"
                                color="rose"
                                class="w-full sm:w-auto"
                            >
                                {{ __('Cancel movement') }}
                            </flux:button>
                        @else
                            <flux:badge variant="subtle" color="slate">
                                {{ __('Cannot cancel') }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
            @empty
                <flux:callout variant="subtle" icon="paper-airplane" class="border-white/10 bg-white/5 text-slate-200 dark:bg-slate-950/60">
                    {{ __('No outgoing orders are currently in motion from this village.') }}
                </flux:callout>
            @endforelse
        </div>

        <div class="space-y-5 rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_30px_90px_-60px_rgba(248,113,113,0.45)] backdrop-blur dark:bg-slate-950/70">
            <div class="flex items-center justify-between">
                <flux:heading size="lg" class="text-white">
                    {{ __('Incoming movements') }}
                </flux:heading>
                <span class="text-xs uppercase tracking-wide text-slate-400">
                    {{ trans_choice(':count movement|:count movements', count($incoming)) }}
                </span>
            </div>

            @forelse ($incoming as $movement)
                @php
                    $coordinates = data_get($movement, 'coordinates');
                    $arrivalIso = $movement['arrive_at'];
                    $arrivalAt = $arrivalIso ? \Illuminate\Support\Carbon::parse($arrivalIso)->timezone($timezone) : null;
                    $remainingSeconds = $movement['remaining_seconds'];
                @endphp

                <div
                    wire:key="incoming-{{ $movement['id'] }}"
                    class="rounded-2xl border border-white/10 bg-white/5 p-5 shadow-[0_10px_35px_-30px_rgba(248,113,113,0.85)] transition hover:border-rose-400 dark:bg-slate-950/60"
                >
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <flux:badge variant="{{ $movement['status_variant'] }}" color="{{ $movement['status_color'] }}">
                                    {{ $movement['status_label'] }}
                                </flux:badge>
                                <span class="text-sm font-semibold text-white">
                                    {{ \Illuminate\Support\Str::headline((string) $movement['movement_type']) }}
                                </span>
                                @if ($movement['mission'])
                                    <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs uppercase tracking-wide text-slate-400">
                                        {{ \Illuminate\Support\Str::headline((string) $movement['mission']) }}
                                    </span>
                                @endif
                            </div>
                            <div class="space-y-1 text-xs text-slate-300">
                                <div>
                                    <span class="uppercase tracking-wide text-slate-400">{{ __('Origin') }}:</span>
                                    <span class="font-semibold text-white">
                                        {{ $movement['village_name'] ?? __('Unknown village') }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="uppercase tracking-wide text-slate-400">{{ __('Owner') }}:</span>
                                    <span class="font-mono text-rose-200">
                                        {{ $movement['owner_name'] ?? __('Unknown') }}
                                    </span>
                                </div>
                                @if (is_array($coordinates))
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="uppercase tracking-wide text-slate-400">{{ __('Coordinates') }}:</span>
                                        <span class="font-mono text-emerald-300">
                                            ({{ data_get($coordinates, 'x', '?') }}, {{ data_get($coordinates, 'y', '?') }})
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2 text-right text-xs text-slate-300">
                            @if ($remainingSeconds !== null)
                                <div
                                    x-data="{
                                        seconds: {{ $remainingSeconds }},
                                        display: '',
                                        timer: null,
                                        arrivedLabel: @js(__('Arrived')),
                                        start() {
                                            this.stop();
                                            this.tick();
                                            this.timer = setInterval(() => this.tick(), 1000);
                                        },
                                        stop() {
                                            if (this.timer) {
                                                clearInterval(this.timer);
                                                this.timer = null;
                                            }
                                        },
                                        tick() {
                                            if (this.seconds <= 0) {
                                                this.display = this.arrivedLabel;
                                                this.stop();
                                                return;
                                            }

                                            this.display = this.format(this.seconds);
                                            this.seconds = Math.max(0, this.seconds - 1);
                                        },
                                        format(value) {
                                            const total = Math.max(0, Math.floor(value));
                                            const hours = Math.floor(total / 3600);
                                            const minutes = Math.floor((total % 3600) / 60);
                                            const seconds = total % 60;

                                            if (hours > 0) {
                                                return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                                            }

                                            return `${minutes}:${String(seconds).padStart(2, '0')}`;
                                        }
                                    }"
                                    x-init="start()"
                                    x-on:beforeunload.window="stop()"
                                    x-on:livewire:navigating.window="stop()"
                                    class="font-mono text-base text-rose-200"
                                >
                                    <span x-text="display"></span>
                                </div>
                            @else
                                <span class="font-mono text-base text-slate-500">—</span>
                            @endif

                            @if ($arrivalAt)
                                <div>
                                    <span class="uppercase tracking-wide text-slate-400">{{ __('ETA') }}:</span>
                                    <span class="font-mono text-rose-200">
                                        {{ $arrivalAt->format('H:i:s') }} {{ $arrivalAt->format('T') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <flux:callout variant="subtle" icon="shield-check" class="border-white/10 bg-white/5 text-slate-200 dark:bg-slate-950/60">
                    {{ __('No hostile or friendly forces are en route to this village right now.') }}
                </flux:callout>
            @endforelse
        </div>
    </section>
</div>
