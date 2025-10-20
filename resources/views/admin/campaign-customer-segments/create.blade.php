@extends('layouts.app')

@section('title', 'Create Campaign Segment')

@section('content')
    <div class="mx-auto max-w-3xl px-6 py-10">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Create campaign segment</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Define who should be included in this reusable customer group.</p>
            </div>
            <a
                href="{{ route('admin.campaign-customer-segments.index') }}"
                class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white"
            >
                Back to list
            </a>
        </div>

        <form action="{{ route('admin.campaign-customer-segments.store') }}" method="POST" class="space-y-8">
            @csrf

            @include('admin.campaign-customer-segments._form', ['schema' => $schema, 'segment' => new \App\Models\CampaignCustomerSegment(), 'filtersJson' => old('filters')])

            <div class="flex items-center justify-end gap-3">
                <a
                    href="{{ route('admin.campaign-customer-segments.index') }}"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Save segment
                </button>
            </div>
        </form>
    </div>
@endsection

