<div class="hero-adventures space-y-8">
    @if (! $hero)
        <div class="hero-adventures__empty rounded border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">
            <h2 class="text-lg font-semibold">No hero selected</h2>
            <p class="mt-2 text-sm">Choose a hero to review ongoing adventures.</p>
        </div>
    @else
        <section class="hero-adventures__summary rounded border border-gray-200 bg-white p-6 shadow-sm">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">{{ $hero->name }}'s adventures</h1>
                    <p class="text-sm text-gray-500">Level {{ $hero->level }} hero · {{ $hero->health }}% health</p>
                </div>
                <div class="text-sm text-gray-500">
                    Total adventures tracked: <strong>{{ $stats['counts']['total'] }}</strong>
                </div>
            </header>

            <dl class="mt-6 grid gap-4 md:grid-cols-4">
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Available</dt>
                    <dd class="mt-2 text-xl font-semibold text-gray-800">{{ $stats['counts']['available'] }}</dd>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">In progress</dt>
                    <dd class="mt-2 text-xl font-semibold text-gray-800">{{ $stats['counts']['in_progress'] }}</dd>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Completed</dt>
                    <dd class="mt-2 text-xl font-semibold text-gray-800">{{ $stats['counts']['completed'] }}</dd>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Failed/Expired</dt>
                    <dd class="mt-2 text-xl font-semibold text-gray-800">{{ $stats['counts']['failed_or_expired'] }}</dd>
                </div>
            </dl>

            <div class="mt-6 grid gap-4 md:grid-cols-3 text-sm text-gray-600">
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-500">Next available adventure</p>
                    <p class="mt-1 font-medium text-gray-800">
                        @if ($stats['next_available_at'])
                            {{ $stats['next_available_at']->diffForHumans() }}
                            <span class="block text-xs text-gray-500">{{ $stats['next_available_at']->toDayDateTimeString() }}</span>
                        @else
                            <span class="text-gray-400">None scheduled</span>
                        @endif
                    </p>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-500">Next completion</p>
                    <p class="mt-1 font-medium text-gray-800">
                        @if ($stats['next_completion_at'])
                            {{ $stats['next_completion_at']->diffForHumans() }}
                            <span class="block text-xs text-gray-500">{{ $stats['next_completion_at']->toDayDateTimeString() }}</span>
                        @else
                            <span class="text-gray-400">No active travel</span>
                        @endif
                    </p>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs uppercase text-gray-500">Last completion</p>
                    <p class="mt-1 font-medium text-gray-800">
                        @if ($stats['last_completed_at'])
                            {{ $stats['last_completed_at']->diffForHumans() }}
                            <span class="block text-xs text-gray-500">{{ $stats['last_completed_at']->toDayDateTimeString() }}</span>
                        @else
                            <span class="text-gray-400">No adventure finished yet</span>
                        @endif
                    </p>
                </div>
            </div>

            @if (! empty($stats['aggregated_rewards']))
                <div class="mt-6 rounded border border-gray-100 bg-gray-50 p-4 text-sm text-gray-600">
                    <h2 class="text-sm font-semibold text-gray-700">Reward totals</h2>
                    <ul class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($stats['aggregated_rewards'] as $reward => $value)
                            <li class="flex items-center justify-between rounded border border-gray-200 bg-white px-3 py-2">
                                <span class="text-xs uppercase text-gray-500">{{ \Illuminate\Support\Str::of($reward)->replace('_', ' ')->headline() }}</span>
                                <span class="font-medium text-gray-900">{{ rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        @forelse ($adventureGroups as $group)
            <section class="hero-adventures__group rounded border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ $group['label'] }}</h2>
                <div class="mt-4 space-y-4">
                    @foreach ($group['items'] as $adventure)
                        @php($rewards = $adventure->rewardSummary())
                        @php($context = $adventure->getAttribute('context') ?? [])
                        <article class="rounded border border-gray-100 bg-gray-50 p-4 shadow-sm">
                            <header class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">
                                        {{ \Illuminate\Support\Str::of($adventure->type)->replace('_', ' ')->headline() }} adventure
                                    </h3>
                                    <p class="text-xs uppercase tracking-wide text-gray-500">Difficulty: {{ $adventure->difficultyLabel() }}</p>
                                </div>
                                <span class="rounded bg-gray-200 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-700">{{ $adventure->statusLabel() }}</span>
                            </header>

                            <dl class="mt-3 grid gap-2 sm:grid-cols-2 text-sm text-gray-600">
                                @if ($adventure->available_at)
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500">Available at</dt>
                                        <dd>{{ $adventure->available_at->toDayDateTimeString() }}</dd>
                                    </div>
                                @endif
                                @if ($adventure->started_at)
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500">Started</dt>
                                        <dd>{{ $adventure->started_at->toDayDateTimeString() }}</dd>
                                    </div>
                                @endif
                                @if ($adventure->completed_at)
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500">Completed</dt>
                                        <dd>{{ $adventure->completed_at->toDayDateTimeString() }}</dd>
                                    </div>
                                @endif
                                @if ($adventure->durationInMinutes())
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500">Duration</dt>
                                        <dd>{{ $adventure->durationInMinutes() }} minutes</dd>
                                    </div>
                                @endif
                                @if ($adventure->origin_village_id)
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500">Origin village</dt>
                                        <dd>#{{ $adventure->origin_village_id }}</dd>
                                    </div>
                                @endif
                                @if ($adventure->target_village_id)
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500">Target village</dt>
                                        <dd>#{{ $adventure->target_village_id }}</dd>
                                    </div>
                                @endif
                            </dl>

                            @if (! empty($rewards))
                                <div class="mt-4">
                                    <h4 class="text-sm font-semibold text-gray-700">Rewards</h4>
                                    <ul class="mt-2 grid gap-2 sm:grid-cols-2 text-sm text-gray-600">
                                        @foreach ($rewards as $label => $value)
                                            <li class="rounded border border-gray-200 bg-white px-3 py-2">
                                                <span class="font-medium text-gray-900">{{ $label }}:</span>
                                                <span class="ml-1 text-gray-700">{{ $value }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (! empty($context))
                                <div class="mt-4">
                                    <h4 class="text-sm font-semibold text-gray-700">Context</h4>
                                    <ul class="mt-2 space-y-1 text-sm text-gray-600">
                                        @foreach ($context as $key => $value)
                                            <li>
                                                <span class="font-medium">{{ \Illuminate\Support\Str::of($key)->replace('_', ' ')->headline() }}:</span>
                                                @if (is_array($value))
                                                    {{ json_encode($value) }}
                                                @elseif (is_bool($value))
                                                    {{ $value ? 'Yes' : 'No' }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <section class="hero-adventures__empty-list rounded border border-gray-200 bg-white p-6 text-center text-gray-500">
                <p>No adventures have been scheduled yet.</p>
            </section>
        @endforelse
    @endif
</div>
