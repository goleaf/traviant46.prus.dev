@php($schema = $schema ?? [])

<div class="space-y-6">
    <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100" for="name">
            {{ $schema['name']['label'] ?? 'Name' }}
        </label>
        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $segment->name ?? '') }}"
            placeholder="{{ $schema['name']['placeholder'] ?? '' }}"
            class="mt-2 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
            required
        >
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $schema['name']['hint'] ?? '' }}</p>
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100" for="slug">
            {{ $schema['slug']['label'] ?? 'Slug' }}
        </label>
        <input
            id="slug"
            name="slug"
            type="text"
            value="{{ old('slug', $segment->slug ?? '') }}"
            placeholder="{{ $schema['slug']['placeholder'] ?? '' }}"
            class="mt-2 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        >
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $schema['slug']['hint'] ?? '' }}</p>
        @error('slug')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100" for="description">
            {{ $schema['description']['label'] ?? 'Description' }}
        </label>
        <textarea
            id="description"
            name="description"
            rows="4"
            placeholder="{{ $schema['description']['placeholder'] ?? '' }}"
            class="mt-2 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        >{{ old('description', $segment->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100" for="filters">
            {{ $schema['filters']['label'] ?? 'Filters' }}
        </label>
        <textarea
            id="filters"
            name="filters"
            rows="8"
            placeholder="{{ $schema['filters']['placeholder'] ?? '' }}"
            class="mt-2 w-full rounded-md border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        >{{ old('filters', $filtersJson ?? '') }}</textarea>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $schema['filters']['hint'] ?? '' }}</p>
        @error('filters')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-2">
        <input
            id="is_active"
            name="is_active"
            type="checkbox"
            value="1"
            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            {{ old('is_active', $segment->is_active ?? true) ? 'checked' : '' }}
        >
        <label for="is_active" class="text-sm text-gray-900 dark:text-gray-100">
            {{ $schema['is_active']['label'] ?? 'Active' }}
        </label>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $schema['is_active']['hint'] ?? '' }}</p>
</div>
