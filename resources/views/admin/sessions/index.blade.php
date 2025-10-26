@extends('layouts.app')

@section('title', 'Active Sessions')

@section('content')
    @php
        use Illuminate\Support\Str;
    @endphp

    <div class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Active Sessions</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Review currently active user sessions, monitor idle activity, and confirm security policies at a glance.
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Sessions</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ number_format($stats['totalSessions']) }}
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Users Online</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ number_format($stats['activeUsers']) }}
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Top Accounts</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    @forelse ($stats['topUsers'] as $topUser)
                        <li class="flex items-center justify-between">
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $topUser->username }}</span>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                {{ $topUser->sessions_count }} active
                            </span>
                        </li>
                    @empty
                        <li class="text-gray-500 dark:text-gray-400">No active accounts.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="mt-8 rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <form method="GET" class="grid gap-4 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label for="search" class="text-sm font-medium text-gray-700 dark:text-gray-300">Search by username or email</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ $filters['search'] }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="jane.doe@example.com"
                    >
                </div>
                <div>
                    <label for="user" class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter by user ID</label>
                    <input
                        type="number"
                        name="user"
                        id="user"
                        value="{{ $filters['user'] }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        placeholder="123"
                    >
                </div>
                <div class="md:col-span-3 flex items-center justify-end gap-3">
                    <a
                        href="{{ route('admin.sessions.index') }}"
                        class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white"
                    >
                        Reset
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Apply filters
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-950">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">User</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Session ID</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">IP Address</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">User Agent</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Last Activity</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Expires</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-sm dark:divide-gray-800 dark:bg-gray-900">
                    @forelse ($sessions as $session)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ optional($session->user)->username ?? 'Unknown user' }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ optional($session->user)->email ?? 'N/A' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                {{ $session->id }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $session->ip_address ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                <span class="block max-w-xs truncate text-xs md:max-w-md">
                                    {{ $session->user_agent ? Str::limit($session->user_agent, 120) : '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ optional($session->last_activity_at)->diffForHumans() ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ optional($session->expires_at)->diffForHumans() ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-600 dark:text-gray-400">
                                No sessions matched the current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $sessions->links() }}
        </div>
    </div>
@endsection
