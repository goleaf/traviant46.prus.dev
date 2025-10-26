@extends('layouts.app')

@section('title', 'Campaign Customer Segments')

@section('content')
    <div class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Campaign Customer Segments</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage reusable customer group definitions used by marketing campaigns.</p>
            </div>
            <a
                href="{{ route('admin.campaign-customer-segments.create') }}"
                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Create segment
            </a>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-700 dark:bg-green-900/50 dark:text-green-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-950">
                    <tr>
                        @foreach ($columns as $key => $label)
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                {{ $label }}
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-sm dark:divide-gray-800 dark:bg-gray-900">
                    @forelse ($segments as $segment)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $segment->name }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $segment->slug }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ number_format($segment->match_count) }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $segment->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/70 dark:text-green-200' : 'bg-gray-200 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                                    {{ $segment->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ optional($segment->last_calculated_at)->diffForHumans() ?? 'Not calculated' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $segment->updated_at->diffForHumans() }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a
                                        href="{{ route('admin.campaign-customer-segments.edit', $segment) }}"
                                        class="rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white"
                                    >
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.campaign-customer-segments.recalculate', $segment) }}" method="POST">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="rounded-md border border-indigo-300 px-3 py-1 text-xs font-medium text-indigo-700 transition hover:border-indigo-400 hover:text-indigo-900 dark:border-indigo-700 dark:text-indigo-300 dark:hover:border-indigo-500 dark:hover:text-white"
                                        >
                                            Recalculate
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.campaign-customer-segments.destroy', $segment) }}" method="POST" onsubmit="return confirm('Delete this segment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="rounded-md border border-red-300 px-3 py-1 text-xs font-medium text-red-700 transition hover:border-red-400 hover:text-red-900 dark:border-red-700 dark:text-red-300 dark:hover:border-red-500 dark:hover:text-white"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="px-4 py-6 text-center text-sm text-gray-600 dark:text-gray-400">
                                No segments have been created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $segments->links() }}
        </div>
    </div>
@endsection

