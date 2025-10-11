@php(
    $state = $inventory?->getAttribute('state') ?? []
)

<div class="hero-inventory space-y-8">
    @if (! $hero)
        <div class="hero-inventory__empty rounded border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">
            <h2 class="text-lg font-semibold">No hero selected</h2>
            <p class="mt-2 text-sm">Create or select a hero to see inventory details.</p>
        </div>
    @else
        <section class="hero-inventory__summary rounded border border-gray-200 bg-white p-6 shadow-sm">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">{{ $hero->name }}</h1>
                    <p class="text-sm text-gray-500">
                        Level {{ $hero->level }} · {{ $hero->health }}% health · {{ ucfirst($hero->status) }}
                    </p>
                </div>
                @if ($inventory?->updated_at)
                    <p class="text-xs text-gray-400">Updated {{ $inventory->updated_at->diffForHumans() }}</p>
                @endif
            </header>

            <dl class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Total slots</dt>
                    <dd class="mt-2 text-xl font-semibold text-gray-800">{{ $summary['total_slots'] }}</dd>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Used slots</dt>
                    <dd class="mt-2 text-xl font-semibold text-gray-800">{{ $summary['used_slots'] }}</dd>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Free slots</dt>
                    <dd class="mt-2 text-xl font-semibold text-gray-800">{{ $summary['free_slots'] }}</dd>
                </div>
            </dl>

            <dl class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Equipped items</dt>
                    <dd class="mt-2 text-xl font-semibold text-gray-800">{{ $summary['equipped_items'] }}</dd>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Backpack stacks</dt>
                    <dd class="mt-2 text-xl font-semibold text-gray-800">{{ $summary['backpack_items'] }}</dd>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 p-4">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Backpack quantity</dt>
                    <dd class="mt-2 text-xl font-semibold text-gray-800">{{ $summary['backpack_quantity'] }}</dd>
                </div>
            </dl>

            @if ($inventory)
                <div class="mt-6 text-sm text-gray-600">
                    <p>
                        Base capacity: <strong>{{ $inventory->capacity }}</strong>
                        @if ($inventory->extra_slots)
                            · Extra slots: <strong>{{ $inventory->extra_slots }}</strong>
                        @endif
                    </p>
                    @if ($inventory->last_water_bucket_used_at)
                        <p class="mt-1">
                            Last water bucket used {{ $inventory->last_water_bucket_used_at->diffForHumans() }}.
                        </p>
                    @endif
                </div>
            @else
                <p class="mt-6 text-sm text-gray-500">Inventory data has not been initialized for this hero yet.</p>
            @endif
        </section>

        <section class="hero-inventory__equipped rounded border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Equipped items</h2>
            @if ($equippedItems->isEmpty())
                <p class="mt-4 text-sm text-gray-500">The hero is not wearing any items.</p>
            @else
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach ($equippedItems as $slot => $itemsInSlot)
                        <div class="rounded border border-gray-100 bg-gray-50 p-4">
                            <h3 class="text-sm font-semibold text-gray-700">{{ \Illuminate\Support\Str::of($slot)->replace('_', ' ')->headline() }}</h3>
                            <ul class="mt-3 space-y-3 text-sm text-gray-600">
                                @foreach ($itemsInSlot as $item)
                                    @php($attributes = $item->formattedAttributes())
                                    <li class="rounded border border-gray-200 bg-white p-3 shadow-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="font-medium text-gray-900">{{ $item->displayName() }}</span>
                                            <span class="rounded bg-gray-200 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-700">{{ strtoupper($item->rarity) }}</span>
                                        </div>
                                        <dl class="mt-2 space-y-1">
                                            <div class="flex justify-between">
                                                <dt class="text-xs uppercase text-gray-500">Quantity</dt>
                                                <dd class="text-xs text-gray-700">{{ $item->quantity }}</dd>
                                            </div>
                                            @if (! empty($attributes))
                                                @foreach ($attributes as $label => $value)
                                                    <div class="flex justify-between">
                                                        <dt class="text-xs uppercase text-gray-500">{{ $label }}</dt>
                                                        <dd class="text-xs text-gray-700">{{ $value }}</dd>
                                                    </div>
                                                @endforeach
                                            @endif
                                            @if ($item->acquired_at)
                                                <div class="flex justify-between">
                                                    <dt class="text-xs uppercase text-gray-500">Acquired</dt>
                                                    <dd class="text-xs text-gray-700">{{ $item->acquired_at->toDateTimeString() }}</dd>
                                                </div>
                                            @endif
                                        </dl>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="hero-inventory__backpack rounded border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Backpack</h2>
            @if ($backpackItems->isEmpty())
                <p class="mt-4 text-sm text-gray-500">The backpack is currently empty.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Slot</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Item</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Rarity</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-600">Quantity</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-600">Slots used</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Attributes</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Acquired</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($backpackItems as $item)
                                @php($attributes = $item->formattedAttributes())
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ \Illuminate\Support\Str::of($item->slot)->replace('_', ' ')->headline() }}</td>
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $item->displayName() }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ ucfirst($item->rarity) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-2 text-right text-gray-700">{{ $item->occupiedSlots() }}</td>
                                    <td class="px-4 py-2 text-gray-700">
                                        @if (! empty($attributes))
                                            <ul class="list-disc space-y-1 pl-5">
                                                @foreach ($attributes as $label => $value)
                                                    <li><span class="font-medium">{{ $label }}:</span> {{ $value }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-700">
                                        @if ($item->acquired_at)
                                            {{ $item->acquired_at->toDateTimeString() }}
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if (! empty($state))
            <section class="hero-inventory__state rounded border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Inventory state</h2>
                <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($state as $key => $value)
                        <div class="rounded border border-gray-100 bg-gray-50 p-4">
                            <dt class="text-xs uppercase tracking-wide text-gray-500">{{ \Illuminate\Support\Str::of($key)->replace('_', ' ')->headline() }}</dt>
                            <dd class="mt-2 text-sm text-gray-700">
                                @if (is_array($value))
                                    {{ json_encode($value) }}
                                @elseif (is_bool($value))
                                    {{ $value ? 'Yes' : 'No' }}
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif
    @endif
</div>
