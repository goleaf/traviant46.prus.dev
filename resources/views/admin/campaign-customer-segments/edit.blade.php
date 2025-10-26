@extends('layouts.app')

@section('title', 'Edit Campaign Segment')

@section('content')
    <div class="mx-auto max-w-3xl px-6 py-10">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Edit campaign segment</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update this customer group and recalculate its matching users.</p>
            </div>
            <a
                href="{{ route('admin.campaign-customer-segments.index') }}"
                class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white"
            >
                Back to list
            </a>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-700 dark:bg-green-900/50 dark:text-green-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-6 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-700 dark:bg-blue-900/50 dark:text-blue-200">
            <p><span class="font-semibold">Current filters:</span> {{ $filtersPreview }}</p>
        </div>

        <form action="{{ route('admin.campaign-customer-segments.update', $segment) }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            @include('admin.campaign-customer-segments._form', ['schema' => $schema, 'segment' => $segment, 'filtersJson' => $filtersJson])

            <div class="flex items-center justify-between gap-3">
                <a
                    href="{{ route('admin.campaign-customer-segments.index') }}"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white"
                >
                    Cancel
                </a>
                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Update segment
                    </button>
                </div>
            </div>
        </form>

        <form action="{{ route('admin.campaign-customer-segments.recalculate', $segment) }}" method="POST" class="mt-8">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center rounded-md border border-indigo-300 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:border-indigo-400 hover:text-indigo-900 dark:border-indigo-700 dark:text-indigo-300 dark:hover:border-indigo-500 dark:hover:text-white"
            >
                Recalculate now
            </button>
            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                Last calculated: {{ optional($segment->last_calculated_at)->diffForHumans() ?? 'Never' }} — Matched users: {{ number_format($segment->match_count) }}
            </p>
        </form>
    </div>
@endsection

