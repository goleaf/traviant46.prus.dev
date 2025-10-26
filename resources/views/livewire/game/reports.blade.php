<div class="relative flex flex-1 overflow-hidden">
    <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(14,116,144,0.08),_transparent_55%)]"></div>
    <div class="pointer-events-none absolute inset-y-0 left-1/2 -z-10 w-[120%] -translate-x-1/2 bg-[radial-gradient(circle,_rgba(8,47,73,0.12),_transparent_65%)]"></div>

    <flux:container class="w-full py-16">
        <div class="space-y-10">
            <div class="rounded-3xl border border-white/10 bg-slate-900/50 p-8 shadow-[0_45px_140px_-60px_rgba(56,189,248,0.55)] backdrop-blur">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <flux:heading level="1" size="xl" class="text-white">
                            {{ __('Combat and World Reports') }}
                        </flux:heading>
                        <flux:description class="max-w-3xl text-slate-300">
                            {{ __('Review your latest combat, scouting, and system reports. Filter by kind and inspect the raw outcomes to plan your next move.') }}
                        </flux:description>
                    </div>

                    <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <flux:field label="{{ __('Filter by kind') }}" class="w-full sm:w-64">
                            <flux:select wire:model.live="kind" placeholder="{{ __('All kinds') }}">
                                @foreach ($kinds as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ __($label) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="rounded-3xl border border-white/10 bg-slate-900/40 backdrop-blur">
                    <div class="divide-y divide-white/5">
                        @forelse ($reports as $report)
                            @php($isSelected = $selectedReport?->is($report) ?? false)

                            <button
                                type="button"
                                class="group block w-full text-left transition"
                                wire:click="selectReport({{ $report->getKey() }})"
                                wire:key="report-{{ $report->getKey() }}"
                            >
                                <div class="flex flex-col gap-3 px-6 py-5 transition first:rounded-t-3xl last:rounded-b-3xl {{ $isSelected ? 'bg-slate-800/70' : 'hover:bg-slate-800/50' }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <flux:badge variant="subtle" color="{{ $isSelected ? 'sky' : 'slate' }}" class="uppercase tracking-wide">
                                                {{ __(ucfirst($report->kind)) }}
                                            </flux:badge>
                                            <span class="text-sm font-medium text-slate-300">
                                                {{ $report->created_at?->diffForHumans() ?? __('Unknown') }}
                                            </span>
                                        </div>

                                        <flux:icon name="chevron-right" class="size-5 text-slate-500 transition group-hover:text-slate-200" />
                                    </div>

                                    <div class="text-sm text-slate-200">
                                        {{ $report->casualties_summary }}
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div class="rounded-3xl px-6 py-12 text-center text-sm text-slate-400">
                                {{ __('No reports to show yet. Engage in missions or trade to generate new reports.') }}
                            </div>
                        @endforelse
                    </div>

                    <div class="border-t border-white/5 px-6 py-4">
                        {{ $reports->links() }}
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-slate-900/40 p-6 backdrop-blur">
                    @if ($selectedReport)
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <flux:heading size="lg" class="text-white">
                                    {{ __(ucfirst($selectedReport->kind).' report') }}
                                </flux:heading>
                                <p class="text-xs uppercase tracking-wide text-slate-400">
                                    {{ __('Logged :timestamp', ['timestamp' => $selectedReport->created_at?->format('M j, Y H:i') ?? __('unknown')]) }}
                                </p>
                            </div>

                            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearSelection">
                                {{ __('Close') }}
                            </flux:button>
                        </div>

                        <div class="mt-6 space-y-5 text-sm text-slate-200">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Casualties') }}</p>
                                <p class="mt-1 leading-relaxed">{{ $selectedReport->casualties_summary }}</p>
                            </div>

                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Bounty') }}</p>
                                <p class="mt-1 leading-relaxed">{{ $selectedReport->bounty_summary }}</p>
                            </div>

                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Damages') }}</p>
                                <p class="mt-1 leading-relaxed">{{ $selectedReport->damages_summary }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex h-full flex-col items-center justify-center gap-3 text-center text-sm text-slate-400">
                            <flux:icon name="document-text" class="size-10 text-slate-500" />
                            <p>{{ __('Select a report from the list to view its full details.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </flux:container>
</div>
