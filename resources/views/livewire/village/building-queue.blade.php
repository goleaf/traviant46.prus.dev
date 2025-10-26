@php
    $entries = data_get($queue, 'entries', []);
    $active = data_get($queue, 'active_entry');
    $userTimezone = optional(auth()->user())->timezone ?? config('app.timezone');
@endphp

<div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 shadow-[0_20px_60px_-40px_rgba(250,204,21,0.45)] dark:bg-slate-950/80">
    @if (empty($entries))
        <flux:callout variant="subtle" icon="sparkles" class="text-slate-200">
            {{ __('No construction orders are currently queued. Visit the infrastructure screen to schedule upgrades.') }}
        </flux:callout>
    @else
        <div class="space-y-4">
            @foreach ($entries as $index => $entry)
                <div wire:key="queue-entry-{{ $entry['id'] ?? $index }}" class="rounded-xl border border-white/10 bg-white/5 p-4 dark:bg-slate-900/80">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-sm font-semibold text-white">
                                <flux:badge variant="{{ data_get($entry, 'is_active') ? 'solid' : 'subtle' }}" color="{{ data_get($entry, 'is_active') ? 'sky' : 'slate' }}">
                                    {{ data_get($entry, 'is_active') ? __('Active') : __('Queued') }}
                                </flux:badge>
                                <span>{{ data_get($entry, 'building_name') }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs uppercase tracking-wide text-slate-400">
                                <span>{{ __('Slot :slot', ['slot' => data_get($entry, 'slot')]) }}</span>
                                <span>Lv {{ data_get($entry, 'current_level') }} → {{ data_get($entry, 'target_level') }}</span>
                                <span>{{ __('Position #:pos', ['pos' => data_get($entry, 'queue_position')]) }}</span>
                            </div>
                        </div>

                        <div class="text-right text-xs text-slate-300">
                            @php
                                $remainingSeconds = (int) data_get($entry, 'remaining_seconds', 0);
                                $completion = data_get($entry, 'completes_at');
                            @endphp

                            @if ($remainingSeconds > 0)
                                <div class="font-mono text-sm text-amber-300">
                                    {{ \Carbon\CarbonInterval::seconds($remainingSeconds)->cascade()->forHumans(['parts' => 2]) }}
                                </div>
                                <div class="text-slate-400">
                                    {{ __('Completion:') }}
                                    <span class="font-mono text-sky-200">
                                        {{ $completion ? \Illuminate\Support\Carbon::parse($completion)->timezone($userTimezone)->format('H:i:s') : '—' }}
                                    </span>
                                </div>
                            @elseif ($completion)
                                <div class="font-mono text-xs text-emerald-300">
                                    {{ __('Finishing shortly') }}
                                </div>
                            @else
                                <div class="font-mono text-xs text-slate-500">
                                    {{ __('Awaiting scheduling') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (!empty(data_get($entry, 'segments')))
                        <div class="mt-4 space-y-2">
                            @foreach (data_get($entry, 'segments', []) as $segmentIndex => $segment)
                                <div wire:key="queue-entry-{{ $entry['id'] }}-segment-{{ $segmentIndex }}" class="flex items-center justify-between rounded-lg border border-white/5 bg-white/5 px-3 py-2 text-xs text-slate-300 dark:bg-slate-900/70">
                                    <span>{{ __('Level :level', ['level' => data_get($segment, 'level')]) }}</span>
                                    <span class="font-mono text-amber-200">
                                        {{ \Carbon\CarbonInterval::seconds((int) data_get($segment, 'duration'))->cascade()->forHumans(['parts' => 2]) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div wire:loading.flex wire:target="refreshQueue" class="mt-4 items-center gap-2 text-sm text-slate-300">
        <flux:icon name="arrow-path" class="size-4 animate-spin text-sky-300" />
        <span>{{ __('Syncing construction queue...') }}</span>
    </div>
</div>
