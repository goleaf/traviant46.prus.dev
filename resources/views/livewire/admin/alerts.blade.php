@php
    $severityStyles = [
        'critical' => 'bg-red-500/15 text-red-200 ring-1 ring-inset ring-red-500/40',
        'high' => 'bg-orange-500/15 text-orange-200 ring-1 ring-inset ring-orange-500/40',
        'medium' => 'bg-amber-500/15 text-amber-100 ring-1 ring-inset ring-amber-500/40',
        'low' => 'bg-sky-500/15 text-sky-200 ring-1 ring-inset ring-sky-500/40',
    ];

    $statusStyles = [
        'open' => 'bg-sky-500/15 text-sky-200 ring-1 ring-inset ring-sky-500/40',
        'suppressed' => 'bg-purple-500/15 text-purple-200 ring-1 ring-inset ring-purple-500/40',
        'resolved' => 'bg-emerald-500/15 text-emerald-200 ring-1 ring-inset ring-emerald-500/40',
        'dismissed' => 'bg-slate-500/15 text-slate-200 ring-1 ring-inset ring-slate-500/40',
    ];

    $severityOptions = $severityOptions ?? [];
    $statusOptions = $statusOptions ?? [];
    $alerts = $alerts ?? null;
    $severityLabels = collect(\App\Enums\MultiAccountAlertSeverity::cases())->mapWithKeys(
        fn ($case) => [$case->value => $case->label()]
    )->all();
@endphp

<div class="relative flex flex-1 overflow-hidden">
    <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.12),_transparent_55%)]"></div>
    <div class="pointer-events-none absolute inset-y-0 left-1/2 -z-10 w-[110%] -translate-x-1/2 bg-[radial-gradient(circle,_rgba(59,130,246,0.08),_transparent_60%)]"></div>

    <flux:container class="w-full py-14">
        <div class="space-y-8">
            <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-8 shadow-[0_35px_120px_-40px_rgba(56,189,248,0.45)] backdrop-blur">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <flux:heading level="1" size="xl" class="text-white">
                            {{ __('Multi-Account Alerts') }}
                        </flux:heading>
                        <flux:description class="max-w-3xl text-slate-300">
                            {{ __('Investigate clustered login activity, upgrade severity filters, and record the resolution trail without leaving the command center.') }}
                        </flux:description>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <flux:button
                            variant="outline"
                            icon="arrow-path"
                            class="w-full sm:w-auto"
                            wire:click="$refresh"
                        >
                            <span>{{ __('Refresh dataset') }}</span>
                        </flux:button>
                        <flux:button
                            variant="ghost"
                            icon="x-mark"
                            class="w-full sm:w-auto text-slate-300 hover:text-white"
                            wire:click="clearFilters"
                        >
                            {{ __('Reset filters') }}
                        </flux:button>
                    </div>
                </div>

                <flux:separator class="my-6" />

                @if (session()->has('status'))
                    <flux:callout
                        variant="success"
                        icon="check-circle"
                        class="border-emerald-400/40 bg-emerald-900/40 text-emerald-100"
                    >
                        {{ session('status') }}
                    </flux:callout>
                @endif

                <div class="mt-6 rounded-2xl border border-white/10 bg-slate-950/70 p-6">
                    <div class="grid gap-4 md:grid-cols-4">
                        <flux:field label="{{ __('Severity') }}">
                            <select
                                wire:model.live="severity"
                                class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-slate-100 shadow-sm focus:border-sky-400 focus:outline-none focus:ring focus:ring-sky-500/40"
                            >
                                <option value="">{{ __('All severities') }}</option>
                                @foreach ($severityOptions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                        </flux:field>

                        <flux:field label="{{ __('Status') }}">
                            <select
                                wire:model.live="status"
                                class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-slate-100 shadow-sm focus:border-sky-400 focus:outline-none focus:ring focus:ring-sky-500/40"
                            >
                                <option value="">{{ __('All statuses') }}</option>
                                @foreach ($statusOptions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                        </flux:field>

                        <flux:field label="{{ __('Search') }}" hint="{{ __('IP, device hash, or player id') }}">
                            <flux:input
                                wire:model.debounce.400ms="search"
                                placeholder="{{ __('203.0.113.* or device hash…') }}"
                            />
                        </flux:field>

                        <div class="flex items-end justify-end">
                            <details class="w-full rounded-xl border border-white/10 bg-slate-900/50 px-4 py-3 text-sm text-slate-200 [&_summary]:cursor-pointer [&_summary]:font-semibold [&_summary]:text-slate-100">
                                <summary>{{ __('Advanced filters') }}</summary>
                                <div class="mt-3 space-y-3">
                                    <div class="space-y-1">
                                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Source type') }}</p>
                                        <select
                                            wire:model.live="sourceType"
                                            class="w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 focus:border-sky-400 focus:outline-none focus:ring focus:ring-sky-500/40"
                                        >
                                            <option value="">{{ __('Any source') }}</option>
                                            <option value="ip">{{ __('IP address') }}</option>
                                            <option value="device">{{ __('Device fingerprint') }}</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Exact IP prefix') }}</p>
                                        <flux:input
                                            wire:model.debounce.400ms="ip"
                                            placeholder="{{ __('198.51.100') }}"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Device hash') }}</p>
                                        <flux:input
                                            wire:model.debounce.400ms="deviceHash"
                                            placeholder="{{ __('Enter full device hash') }}"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('World identifier') }}</p>
                                        <flux:input
                                            wire:model.debounce.400ms="worldId"
                                            placeholder="{{ __('world-1') }}"
                                        />
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                @if (!empty($sharedIpGroups))
                    <div class="mt-8 grid gap-4 lg:grid-cols-3 sm:grid-cols-2">
                        @foreach ($sharedIpGroups as $group)
                            <div class="rounded-2xl border border-sky-400/20 bg-slate-900/60 p-5 shadow-inner shadow-sky-500/10">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-sky-300">{{ __('World') }}</p>
                                        <p class="text-lg font-semibold text-slate-100">{{ $group['label'] }}</p>
                                    </div>
                                    <span class="rounded-full bg-sky-500/20 px-3 py-1 text-xs font-medium text-sky-100">
                                        {{ trans_choice('{0}0 IP alerts|{1}:count IP alert|[2,*]:count IP alerts', $group['total'], ['count' => $group['total']]) }}
                                    </span>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                    @php
                                        $nonZero = 0;
                                    @endphp
                                    @foreach ($group['by_severity'] as $severity => $count)
                                        @if ($count > 0)
                                            @php $nonZero++; @endphp
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-800/70 px-3 py-1 font-semibold text-slate-200">
                                                <span class="text-[11px] uppercase tracking-wide">{{ $severityLabels[$severity] ?? strtoupper($severity) }}</span>
                                                <span>{{ $count }}</span>
                                            </span>
                                        @endif
                                    @endforeach
                                    @if ($nonZero === 0)
                                        <span class="text-slate-400">{{ __('All alerts below severity thresholds.') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-8 overflow-hidden rounded-2xl border border-white/10">
                    <table class="min-w-full divide-y divide-white/10 text-left text-sm text-slate-200">
                        <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-3 font-semibold">{{ __('Source') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ __('Severity') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ __('Accounts') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ __('Occurrences') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ __('Last seen') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($alerts as $alert)
                                @php
                                    $severityValue = $alert->severity?->value;
                                    $statusValue = $alert->status?->value;
                                    $sourceIdentifier = $alert->source_type === 'device'
                                        ? $alert->device_hash
                                        : $alert->ip_address;
                                @endphp
                                <tr
                                    wire:key="alert-row-{{ $alert->getKey() }}"
                                    class="bg-slate-950/50 transition-colors hover:bg-slate-900/70"
                                >
                                    <td class="px-4 py-4 align-top text-sm">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[13px] font-semibold uppercase tracking-wide text-slate-300">
                                                {{ $alert->source_type === 'device' ? __('Device hash') : __('IP address') }}
                                            </span>
                                            <span class="font-mono text-xs text-slate-400">
                                                {{ $sourceIdentifier ?? __('Unknown') }}
                                            </span>
                                            <span class="text-[11px] text-slate-500">
                                                {{ $alert->world_id ? __('World: :world', ['world' => $alert->world_id]) : __('World: Global') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $severityStyles[$severityValue] ?? 'bg-slate-700/40 text-slate-200' }}">
                                            {{ $alert->severity?->label() ?? __('Unknown') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusStyles[$statusValue] ?? 'bg-slate-700/40 text-slate-200' }}">
                                            {{ $alert->status?->label() ?? __('Unknown') }}
                                        </span>
                                        @if ($alert->resolved_at)
                                            <p class="mt-2 text-[11px] text-emerald-300">
                                                {{ __('Resolved :time by :user', [
                                                    'time' => $alert->resolved_at->diffForHumans(),
                                                    'user' => optional($alert->resolvedBy)->username ?? __('system'),
                                                ]) }}
                                            </p>
                                        @elseif ($alert->dismissed_at)
                                            <p class="mt-2 text-[11px] text-slate-300">
                                                {{ __('Dismissed :time by :user', [
                                                    'time' => $alert->dismissed_at->diffForHumans(),
                                                    'user' => optional($alert->dismissedBy)->username ?? __('system'),
                                                ]) }}
                                            </p>
                                        @elseif ($alert->suppression_reason)
                                            <p class="mt-2 text-[11px] text-purple-200">
                                                {{ __('Suppressed: :reason', ['reason' => $alert->suppression_reason]) }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="text-sm font-semibold text-slate-100">
                                            {{ number_format(count($alert->user_ids ?? [])) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="text-sm text-slate-200">
                                            {{ number_format((int) $alert->occurrences) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex flex-col gap-1 text-xs text-slate-300">
                                            <span>{{ optional($alert->last_seen_at)?->diffForHumans() ?? __('Unknown') }}</span>
                                            <span class="text-slate-500">{{ optional($alert->window_started_at)?->toDayDateTimeString() }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex flex-col gap-2">
                                            <a
                                                href="{{ route('admin.multi-account-alerts.show', $alert) }}"
                                                class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-sky-400 hover:text-white"
                                            >
                                                <flux:icon name="eye" class="size-4" />
                                                <span>{{ __('Inspect') }}</span>
                                            </a>

                                            @can('resolve', $alert)
                                                <flux:button
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="check"
                                                    wire:click="confirmResolve({{ $alert->getKey() }})"
                                                    class="justify-start text-emerald-300 hover:text-emerald-100"
                                                >
                                                    {{ __('Resolve') }}
                                                </flux:button>
                                            @endcan

                                            @can('dismiss', $alert)
                                                <flux:button
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="x-mark"
                                                    wire:click="confirmDismiss({{ $alert->getKey() }})"
                                                    class="justify-start text-rose-300 hover:text-rose-100"
                                                >
                                                    {{ __('Dismiss') }}
                                                </flux:button>
                                            @endcan

                                            @if ($alert->notes)
                                                <p class="text-[11px] text-slate-400">
                                                    {{ __('Note: :note', ['note' => \Illuminate\Support\Str::limit($alert->notes, 120)]) }}
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-300">
                                        {{ __('No alerts match the current filters.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($alerts)
                    <div class="mt-6">
                        {{ $alerts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </flux:container>
</div>

@if ($actionContext)
    <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-2xl border border-white/10 bg-slate-900/90 p-8 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg" class="text-white">
                        {{ $actionType === 'resolve' ? __('Resolve alert') : __('Dismiss alert') }}
                    </flux:heading>
                    <flux:description class="mt-2 text-slate-300">
                        {{ __('Record how this investigation was handled. Notes are stored with the audit trail and visible to fellow hunters.') }}
                    </flux:description>
                </div>
                <button
                    type="button"
                    wire:click="cancelAction"
                    class="rounded-full border border-white/10 p-2 text-slate-300 transition hover:border-slate-200/30 hover:text-white"
                >
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>

            <dl class="mt-5 grid gap-3 rounded-xl border border-white/10 bg-slate-950/60 p-4 text-sm text-slate-200">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-400">{{ __('Source') }}</dt>
                    <dd class="font-mono text-xs">{{ $actionContext['source_type'] === 'device' ? __('Device hash') : __('IP address') }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-400">{{ __('Identifier') }}</dt>
                    <dd class="font-mono text-xs break-all text-slate-100">{{ $actionContext['identifier'] ?? __('Unknown') }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-400">{{ __('Severity') }}</dt>
                    <dd class="text-slate-200">{{ $actionContext['severity'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-400">{{ __('Accounts involved') }}</dt>
                    <dd class="text-slate-200">{{ number_format($actionContext['accounts']) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-400">{{ __('Occurrences') }}</dt>
                    <dd class="text-slate-200">{{ number_format($actionContext['occurrences']) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-400">{{ __('Last seen') }}</dt>
                    <dd class="text-slate-200">{{ $actionContext['last_seen'] }}</dd>
                </div>
            </dl>

            <form wire:submit.prevent="performAction" class="mt-6 space-y-4">
                <flux:field
                    label="{{ __('Audit note') }}"
                    hint="{{ __('Optional context for future reviewers (max 1000 characters).') }}"
                >
                    <flux:textarea
                        wire:model.defer="notes"
                        rows="4"
                        placeholder="{{ __('Flagged by VPN heuristics, confirmed by sitter log…') }}"
                    />
                </flux:field>
                @error('notes')
                    <p class="text-sm text-rose-300">{{ $message }}</p>
                @enderror

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <flux:description class="text-xs text-slate-400">
                        {{ __('Actions immediately update the alert status and notify collaborating staff dashboards.') }}
                    </flux:description>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <flux:button
                            type="submit"
                            icon="{{ $actionType === 'resolve' ? 'check' : 'x-mark' }}"
                            color="{{ $actionType === 'resolve' ? 'emerald' : 'rose' }}"
                        >
                            {{ $actionType === 'resolve' ? __('Resolve alert') : __('Dismiss alert') }}
                        </flux:button>
                        <flux:button
                            type="button"
                            variant="ghost"
                            icon="arrow-uturn-left"
                            wire:click="cancelAction"
                            class="text-slate-300 hover:text-white"
                        >
                            {{ __('Cancel') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif
