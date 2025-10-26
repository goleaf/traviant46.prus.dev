@php($schema = $schema ?? [])

<div class="space-y-6">
    <div>
        @php($nameErrorId = 'segment-name-error')
        @php($nameHint = $schema['name']['hint'] ?? null)
        @php($nameHintId = $nameHint ? 'segment-name-hint' : null)
        @php($nameDescribedBy = trim(implode(' ', array_filter([$nameHintId, $errors->has('name') ? $nameErrorId : null]))))
        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100" for="name">
            {{ __($schema['name']['label'] ?? __('admin.campaign_customer_segments.fields.name.label')) }}
        </label>
        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $segment->name ?? '') }}"
            placeholder="{{ $schema['name']['placeholder'] ?? '' }}"
            class="mt-2 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
            required
            @if ($nameDescribedBy !== '')
                aria-describedby="{{ $nameDescribedBy }}"
            @endif
            @if ($errors->has('name'))
                aria-invalid="true"
            @endif
        >
        @if ($nameHint)
            <p id="{{ $nameHintId }}" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __($nameHint) }}</p>
        @endif
        @error('name')
            <p id="{{ $nameErrorId }}" class="mt-1 text-sm text-red-600" role="alert" aria-live="polite">{{ $message }}</p>
        @enderror
    </div>

    <div>
        @php($slugErrorId = 'segment-slug-error')
        @php($slugHint = $schema['slug']['hint'] ?? null)
        @php($slugHintId = $slugHint ? 'segment-slug-hint' : null)
        @php($slugDescribedBy = trim(implode(' ', array_filter([$slugHintId, $errors->has('slug') ? $slugErrorId : null]))))
        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100" for="slug">
            {{ __($schema['slug']['label'] ?? __('admin.campaign_customer_segments.fields.slug.label')) }}
        </label>
        <input
            id="slug"
            name="slug"
            type="text"
            value="{{ old('slug', $segment->slug ?? '') }}"
            placeholder="{{ $schema['slug']['placeholder'] ?? '' }}"
            class="mt-2 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
            @if ($slugDescribedBy !== '')
                aria-describedby="{{ $slugDescribedBy }}"
            @endif
            @if ($errors->has('slug'))
                aria-invalid="true"
            @endif
        >
        @if ($slugHint)
            <p id="{{ $slugHintId }}" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __($slugHint) }}</p>
        @endif
        @error('slug')
            <p id="{{ $slugErrorId }}" class="mt-1 text-sm text-red-600" role="alert" aria-live="polite">{{ $message }}</p>
        @enderror
    </div>

    <div>
        @php($descriptionErrorId = 'segment-description-error')
        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100" for="description">
            {{ __($schema['description']['label'] ?? __('admin.campaign_customer_segments.fields.description.label')) }}
        </label>
        <textarea
            id="description"
            name="description"
            rows="4"
            placeholder="{{ $schema['description']['placeholder'] ?? '' }}"
            class="mt-2 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
            @if ($errors->has('description'))
                aria-invalid="true"
                aria-describedby="{{ $descriptionErrorId }}"
            @endif
        >{{ old('description', $segment->description ?? '') }}</textarea>
        @error('description')
            <p id="{{ $descriptionErrorId }}" class="mt-1 text-sm text-red-600" role="alert" aria-live="polite">{{ $message }}</p>
        @enderror
    </div>

    <div>
        @php($filtersErrorId = 'segment-filters-error')
        @php($filtersHint = $schema['filters']['hint'] ?? null)
        @php($filtersHintId = $filtersHint ? 'segment-filters-hint' : null)
        @php($filtersDescribedBy = trim(implode(' ', array_filter([$filtersHintId, $errors->has('filters') ? $filtersErrorId : null]))))
        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100" for="filters">
            {{ __($schema['filters']['label'] ?? __('admin.campaign_customer_segments.fields.filters.label')) }}
        </label>
        <textarea
            id="filters"
            name="filters"
            rows="8"
            placeholder="{{ $schema['filters']['placeholder'] ?? '' }}"
            class="mt-2 w-full rounded-md border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
            @if ($filtersDescribedBy !== '')
                aria-describedby="{{ $filtersDescribedBy }}"
            @endif
            @if ($errors->has('filters'))
                aria-invalid="true"
            @endif
        >{{ old('filters', $filtersJson ?? '') }}</textarea>
        @if ($filtersHint)
            <p id="{{ $filtersHintId }}" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __($filtersHint) }}</p>
        @endif
        @error('filters')
            <p id="{{ $filtersErrorId }}" class="mt-1 text-sm text-red-600" role="alert" aria-live="polite">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-2">
        @php($activeHint = $schema['is_active']['hint'] ?? null)
        @php($activeHintId = $activeHint ? 'segment-active-hint' : null)
        <input
            id="is_active"
            name="is_active"
            type="checkbox"
            value="1"
            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            {{ old('is_active', $segment->is_active ?? true) ? 'checked' : '' }}
            @if ($activeHintId)
                aria-describedby="{{ $activeHintId }}"
            @endif
        >
        <label for="is_active" class="text-sm text-gray-900 dark:text-gray-100">
            {{ __($schema['is_active']['label'] ?? __('admin.campaign_customer_segments.fields.is_active.label')) }}
        </label>
    </div>
    @if ($activeHint)
        <p id="{{ $activeHintId }}" class="text-xs text-gray-500 dark:text-gray-400">{{ __($activeHint) }}</p>
    @endif
</div>
