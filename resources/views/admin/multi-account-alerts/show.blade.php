@extends('layouts.app')

@section('title', 'Alert '.$alert->alert_id)

@php
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Alert {{ $alert->alert_id }}</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Explaining correlated activity for {{ $alert->source_type === 'device' ? 'device hash' : 'IP address' }} <span class="font-mono text-gray-800 dark:text-gray-200">{{ $alert->source_type === 'device' ? $alert->device_hash : $alert->ip_address }}</span>.</p>
            </div>
            <a href="{{ route('admin.multi-account-alerts.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white">Back to alerts</a>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-700 dark:bg-green-900/50 dark:text-green-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Severity</dt>
                            <dd class="mt-1 inline-flex items-center rounded-full bg-blue-500/10 px-3 py-1 text-sm font-semibold text-blue-700 dark:bg-blue-500/20 dark:text-blue-200">
                                {{ $alert->severity?->label() ?? 'Unknown' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1 inline-flex items-center rounded-full bg-gray-500/10 px-3 py-1 text-sm font-semibold text-gray-700 dark:bg-gray-500/20 dark:text-gray-200">
                                {{ $alert->status?->label() ?? 'Unknown' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">First seen</dt>
                            <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ optional($alert->first_seen_at)->toDayDateTimeString() ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Last seen</dt>
                            <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ optional($alert->last_seen_at)->toDayDateTimeString() ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Window</dt>
                            <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {{ optional($alert->window_started_at)->toDayDateTimeString() ?? 'Unknown' }} → {{ optional($alert->last_seen_at)->toDayDateTimeString() ?? 'Unknown' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Occurrences</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ number_format((int) $alert->occurrences) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Suppression</dt>
                            <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $alert->suppression_reason ? Str::headline(str_replace('_', ' ', $alert->suppression_reason)) : 'Not suppressed' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">VPN heuristics</dt>
                            <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $vpnSuspected ? 'Likely VPN / hosting provider' : 'No VPN indicators detected' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Involved accounts</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Accounts observed on this {{ $alert->source_type }} within the configured window.</p>
                    <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($alert->user_ids ?? [] as $userId)
                            @php
                                $user = $users->get($userId);
                                $count = Arr::get($alert->metadata['user_counts'] ?? [], (string) $userId, 0);
                            @endphp
                            <li class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 shadow-sm dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $user?->username ?? 'User #'.$userId }}</span>
                                    <span class="text-xs text-gray-500">{{ $count }} logins</span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">User ID: {{ $userId }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent login activity</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Most recent login entries correlated with this alert.</p>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-950">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">User</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">IP Address</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Device hash</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Sitter</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Logged at</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @forelse ($activities as $activity)
                                    <tr class="bg-white dark:bg-gray-900">
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                            {{ $activity->user?->username ?? 'User #'.$activity->user_id }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $activity->ip_address }}</td>
                                        <td class="px-3 py-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $activity->device_hash ?? '—' }}</td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                            @if ($activity->via_sitter)
                                                Sitter #{{ $activity->acting_sitter_id }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ optional($activity->logged_at)->toDayDateTimeString() ?? 'Unknown' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-600 dark:text-gray-400">No recent activity recorded for this alert.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Take action</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Resolve when the incident has been addressed, or dismiss if the alert is a false positive.</p>

                    <form action="{{ route('admin.multi-account-alerts.resolve', $alert) }}" method="POST" class="mt-4 space-y-3">
                        @csrf
                        <label for="resolve-notes" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Notes</label>
                        <textarea id="resolve-notes" name="notes" rows="3" class="w-full rounded-md border-gray-300 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200" placeholder="Summarize the remediation or findings."></textarea>
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">Resolve alert</button>
                    </form>

                    <div class="my-4 border-t border-gray-200 dark:border-gray-800"></div>

                    <form action="{{ route('admin.multi-account-alerts.dismiss', $alert) }}" method="POST" class="space-y-3">
                        @csrf
                        <label for="dismiss-notes" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dismissal rationale</label>
                        <textarea id="dismiss-notes" name="notes" rows="3" class="w-full rounded-md border-gray-300 text-sm text-gray-800 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200" placeholder="Explain why this alert is being dismissed."></textarea>
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Dismiss alert</button>
                    </form>

                    @if ($alert->notes)
                        <div class="mt-6 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Existing notes</h3>
                            <p class="mt-2 whitespace-pre-line">{{ $alert->notes }}</p>
                        </div>
                    @endif
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900" x-data="ipLookup({ endpoint: '{{ route('admin.multi-account-alerts.ip-lookup') }}', initialIp: '{{ $alert->ip_address }}' })">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">IP intelligence</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Look up IP ownership details with built-in rate limiting.</p>

                    <form class="mt-4 space-y-3" @submit.prevent="lookup">
                        <label for="lookup-ip" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">IP address</label>
                        <input id="lookup-ip" x-model="ip" type="text" class="w-full rounded-md border-gray-300 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200" placeholder="Enter an IPv4 or IPv6 address" />
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" :disabled="loading">
                            <span x-show="!loading">Lookup address</span>
                            <span x-show="loading">Looking up…</span>
                        </button>
                    </form>

                    <template x-if="error">
                        <p class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/40 dark:text-red-200" x-text="error"></p>
                    </template>

                    <template x-if="result">
                        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm text-gray-700 dark:text-gray-300">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">IP</dt>
                                <dd class="mt-1" x-text="result.ip"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Region</dt>
                                <dd class="mt-1" x-text="[result.city, result.region, result.country].filter(Boolean).join(', ') || 'Unknown'"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">ISP</dt>
                                <dd class="mt-1" x-text="result.isp || 'Unknown'"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">ASN</dt>
                                <dd class="mt-1" x-text="result.asn || 'Unknown'"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Flags</dt>
                                <dd class="mt-1" x-text="['proxy', 'hosting', 'mobile'].filter(flag => result[flag]).map(flag => flag.toUpperCase()).join(', ') || 'None detected'"></dd>
                            </div>
                        </dl>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('ipLookup', ({ endpoint, initialIp }) => ({
                endpoint,
                ip: initialIp || '',
                loading: false,
                error: null,
                result: null,
                async lookup() {
                    if (!this.ip) {
                        this.error = 'Enter an IP address to continue.';
                        return;
                    }

                    this.loading = true;
                    this.error = null;
                    this.result = null;

                    try {
                        const response = await fetch(`${this.endpoint}?ip=${encodeURIComponent(this.ip)}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });

                        const body = await response.json();

                        if (!response.ok) {
                            throw new Error(body.message ?? 'Lookup failed.');
                        }

                        this.result = body.data ?? body;
                    } catch (error) {
                        this.error = error.message;
                    } finally {
                        this.loading = false;
                    }
                },
            }));
        });
    </script>
@endpush
