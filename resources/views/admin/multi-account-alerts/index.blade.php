@extends('layouts.app')

@section('title', 'Multi-Account Alerts')

@section('content')
    <div class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Multi-Account Alerts</h1>
                <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-400">Review correlated login activity, track severity tiers, and resolve or dismiss investigations.</p>
            </div>
            <form method="GET" action="{{ route('admin.multi-account-alerts.index') }}" class="grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 md:grid-cols-3">
                <div>
                    <label for="severity" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Severity</label>
                    <select id="severity" name="severity" class="mt-1 w-full rounded-md border-gray-300 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                        <option value="">All severities</option>
                        @foreach ($severityOptions as $option)
                            <option value="{{ $option->value }}" @selected(($filters['severity'] ?? null) === $option->value)>
                                {{ $option->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</label>
                    <select id="status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $option)
                            <option value="{{ $option->value }}" @selected(($filters['status'] ?? null) === $option->value)>
                                {{ $option->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Search</label>
                    <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="IP, device hash, or user id" class="mt-1 w-full rounded-md border-gray-300 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200" />
                </div>
                <div class="md:col-span-3 flex items-center gap-3">
                    <input type="hidden" name="source_type" value="{{ $filters['source_type'] ?? '' }}">
                    <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Apply filters</button>
                    <a href="{{ route('admin.multi-account-alerts.index') }}" class="text-sm font-medium text-gray-600 transition hover:text-gray-800 dark:text-gray-300 dark:hover:text-white">Reset</a>
                </div>
            </form>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-700 dark:bg-green-900/50 dark:text-green-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-950">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Source</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Severity</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Status</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Accounts</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Occurrences</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Last seen</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-sm dark:divide-gray-800 dark:bg-gray-900">
                    @forelse ($alerts as $alert)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ strtoupper($alert->source_type) }}</span>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $alert->source_type === 'device' ? $alert->device_hash : $alert->ip_address }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $severity = $alert->severity?->value;
                                    $severityStyles = [
                                        'critical' => 'bg-red-600/10 text-red-700 dark:bg-red-500/20 dark:text-red-200',
                                        'high' => 'bg-orange-500/10 text-orange-700 dark:bg-orange-500/20 dark:text-orange-200',
                                        'medium' => 'bg-yellow-400/10 text-yellow-700 dark:bg-yellow-400/20 dark:text-yellow-200',
                                        'low' => 'bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-200',
                                    ];
                                @endphp
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $severityStyles[$severity] ?? 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                    {{ $alert->severity?->label() ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $alert->status === \App\Enums\MultiAccountAlertStatus::Resolved ? 'bg-green-500/10 text-green-700 dark:bg-green-500/20 dark:text-green-200' : ($alert->status === \App\Enums\MultiAccountAlertStatus::Dismissed ? 'bg-gray-500/10 text-gray-700 dark:bg-gray-500/20 dark:text-gray-200' : ($alert->status === \App\Enums\MultiAccountAlertStatus::Suppressed ? 'bg-purple-500/10 text-purple-700 dark:bg-purple-500/20 dark:text-purple-200' : 'bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-200')) }}">
                                    {{ $alert->status?->label() ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ count($alert->user_ids ?? []) }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ number_format((int) $alert->occurrences) }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ optional($alert->last_seen_at)->diffForHumans() ?? 'Unknown' }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.multi-account-alerts.show', $alert) }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-600 dark:text-gray-400">No alerts match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $alerts->links() }}
        </div>
    </div>
@endsection
