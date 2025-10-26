@php
    $sections = [
        [
            'key' => 'tutorial',
            'title' => __('Tutorial quests'),
            'description' => __('Learn the basics of ruling your village by completing the guided steps below.'),
            'quests' => $tutorialQuests,
        ],
        [
            'key' => 'daily',
            'title' => __('Daily quests'),
            'description' => __('Keep your empire efficient with repeatable objectives that reset each day.'),
            'quests' => $dailyQuests,
        ],
    ];
@endphp

<div class="space-y-6">
    @if ($statusMessage)
        <flux:callout variant="subtle" icon="check-circle" class="border-emerald-500/20 bg-emerald-500/10 text-emerald-100">
            {{ $statusMessage }}
        </flux:callout>
    @endif

    <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_30px_80px_-40px_rgba(15,23,42,0.65)] backdrop-blur">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <flux:heading size="lg" class="text-white">{{ __('Quest log') }}</flux:heading>
                <flux:description class="text-slate-400">
                    {{ __('Track your progress across tutorial and daily objectives, review reward bundles, and complete tasks as you grow.') }}
                </flux:description>
            </div>

            <div class="flex items-center gap-3">
                <flux:button
                    variant="outline"
                    icon="arrow-path"
                    wire:click="refreshLog"
                    wire:target="refreshLog"
                    wire:loading.attr="disabled"
                    class="min-w-[140px]"
                >
                    <span class="flex items-center gap-2">
                        <span>{{ __('Refresh') }}</span>
                        <span wire:loading wire:target="refreshLog" class="text-xs uppercase tracking-wide text-slate-400">
                            {{ __('Updating…') }}
                        </span>
                    </span>
                </flux:button>
            </div>
        </div>

        <flux:separator class="my-8" />

        <div class="space-y-12">
            @foreach ($sections as $section)
                <div class="space-y-6" wire:key="quest-section-{{ $section['key'] }}">
                    <div class="space-y-1">
                        <flux:heading size="md" class="text-white">
                            {{ $section['title'] }}
                        </flux:heading>
                        <p class="text-sm text-slate-400">
                            {{ $section['description'] }}
                        </p>
                    </div>

                    @if (empty($section['quests']))
                        <flux:callout variant="subtle" icon="information-circle" class="border-white/10 bg-slate-950/60 text-slate-300">
                            {{ __('No quests are available in this category yet. Check back soon!') }}
                        </flux:callout>
                    @else
                        <div class="space-y-5">
                            @foreach ($section['quests'] as $quest)
                                <div
                                    wire:key="quest-card-{{ $section['key'] }}-{{ $quest['id'] }}"
                                    class="rounded-2xl border border-white/10 bg-slate-950/60 p-6 transition hover:border-white/20 hover:bg-slate-900/60"
                                >
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div class="space-y-2">
                                            <flux:heading size="sm" class="text-white">
                                                {{ $quest['title'] }}
                                            </flux:heading>
                                            <p class="text-sm text-slate-400">
                                                {{ $quest['description'] }}
                                            </p>
                                        </div>

                                        <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center">
                                            @php
                                                $isCompleted = ($quest['state'] ?? null) === \App\Models\Game\QuestProgress::STATE_COMPLETED;
                                            @endphp
                                            <flux:badge variant="solid" color="{{ $isCompleted ? 'emerald' : 'sky' }}">
                                                {{ $quest['state_label'] ?? ($isCompleted ? __('Completed') : __('In progress')) }}
                                            </flux:badge>

                                            @if ($isCompleted)
                                                <span class="text-xs uppercase tracking-wide text-emerald-200">
                                                    {{ $quest['completed_human'] ? __('Completed :time ago', ['time' => $quest['completed_human']]) : __('Completed') }}
                                                </span>
                                            @else
                                                <flux:button
                                                    size="sm"
                                                    icon="check-circle"
                                                    wire:click="completeQuest({{ $quest['id'] }})"
                                                    wire:target="completeQuest({{ $quest['id'] }})"
                                                    wire:loading.attr="disabled"
                                                    class="min-w-[160px]"
                                                >
                                                    <span class="flex items-center gap-2">
                                                        <span>{{ __('Mark complete') }}</span>
                                                        <span
                                                            wire:loading
                                                            wire:target="completeQuest({{ $quest['id'] }})"
                                                            class="text-xs uppercase tracking-wide text-slate-200"
                                                        >
                                                            {{ __('Working…') }}
                                                        </span>
                                                    </span>
                                                </flux:button>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-6 grid gap-6 md:grid-cols-2">
                                        <div class="space-y-3">
                                            <div class="flex items-center gap-2">
                                                <flux:icon name="flag" class="size-4 text-sky-300" />
                                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-300">
                                                    {{ __('Conditions') }}
                                                </span>
                                            </div>

                                            @if (empty($quest['objectives']))
                                                <p class="text-sm text-slate-400">
                                                    {{ __('No conditions were defined for this quest.') }}
                                                </p>
                                            @else
                                                <ul class="space-y-2">
                                                    @foreach ($quest['objectives'] as $objective)
                                                        <li
                                                            wire:key="objective-{{ $section['key'] }}-{{ $quest['id'] }}-{{ $objective['key'] ?? $loop->index }}"
                                                            class="rounded-xl border border-white/5 bg-slate-900/60 px-4 py-3"
                                                        >
                                                            <div class="flex items-center justify-between gap-3">
                                                                <span class="text-sm font-medium text-slate-100">
                                                                    {{ $objective['label'] ?? __('Objective') }}
                                                                </span>
                                                                @if (! empty($objective['completed']))
                                                                    <flux:badge variant="subtle" color="emerald">
                                                                        {{ __('Done') }}
                                                                    </flux:badge>
                                                                @endif
                                                            </div>
                                                            @if (! empty($objective['description']))
                                                                <p class="mt-1 text-xs text-slate-400">
                                                                    {{ $objective['description'] }}
                                                                </p>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>

                                        <div class="space-y-3">
                                            <div class="flex items-center gap-2">
                                                <flux:icon name="gift" class="size-4 text-amber-300" />
                                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-300">
                                                    {{ __('Rewards') }}
                                                </span>
                                            </div>

                                            @if (empty($quest['rewards']))
                                                <p class="text-sm text-slate-400">
                                                    {{ __('Rewards will be announced soon.') }}
                                                </p>
                                            @else
                                                <ul class="space-y-2">
                                                    @foreach ($quest['rewards'] as $reward)
                                                        <li
                                                            wire:key="reward-{{ $section['key'] }}-{{ $quest['id'] }}-{{ $reward['key'] ?? $loop->index }}"
                                                            class="rounded-xl border border-amber-500/20 bg-amber-500/5 px-4 py-3"
                                                        >
                                                            <div class="flex items-center justify-between gap-3">
                                                                <span class="text-sm font-medium text-amber-100">
                                                                    {{ $reward['label'] ?? __('Reward') }}
                                                                </span>
                                                                @if (! empty($reward['claimable']) && empty($reward['claimed']))
                                                                    <flux:badge variant="subtle" color="amber">
                                                                        {{ __('Ready to claim') }}
                                                                    </flux:badge>
                                                                @elseif (! empty($reward['claimed']))
                                                                    <flux:badge variant="subtle" color="emerald">
                                                                        {{ __('Claimed') }}
                                                                    </flux:badge>
                                                                @endif
                                                            </div>
                                                            @if (! empty($reward['details']))
                                                                <p class="mt-1 text-xs text-amber-200">
                                                                    {{ $reward['details'] }}
                                                                </p>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
