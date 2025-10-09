<?php

namespace App\Services\Game;

use App\Models\BuildingQueueEntry;
use App\Models\Village;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class BuildingQueueService
{
    public function getQueueSnapshot(Village $village): Collection
    {
        $entries = $village->buildingQueueEntries()
            ->orderBy('finishes_at')
            ->get()
            ->map(fn (BuildingQueueEntry $entry) => $this->transformEntry($entry));

        if ($entries->isNotEmpty()) {
            return $entries;
        }

        $snapshot = collect($village->queue_snapshot ?? [])
            ->map(function (array|Arrayable $entry) {
                $data = $entry instanceof Arrayable ? $entry->toArray() : $entry;

                return [
                    'building' => Arr::get($data, 'building'),
                    'target_level' => (int) Arr::get($data, 'target_level', 0),
                    'slot' => (int) Arr::get($data, 'slot', 0),
                    'finishes_at' => Arr::get($data, 'finishes_at'),
                    'cost' => Arr::get($data, 'cost', []),
                ];
            });

        if ($snapshot->isNotEmpty()) {
            return $snapshot;
        }

        return collect([
            [
                'building' => 'main_building',
                'target_level' => 1,
                'slot' => 1,
                'finishes_at' => Date::now()->addMinutes(5)->toIso8601String(),
                'cost' => ['wood' => 70, 'clay' => 40, 'iron' => 60, 'crop' => 20],
            ],
        ]);
    }

    public function queueBuilding(Village $village, array $validated): BuildingQueueEntry
    {
        return $village->buildingQueueEntries()->create([
            'building_key' => $validated['building_key'],
            'target_level' => $validated['target_level'],
            'slot' => $validated['slot'],
            'finishes_at' => Date::now()->addMinutes($validated['construction_time'] ?? 5),
            'cost' => $validated['cost'] ?? [],
        ]);
    }

    public function availableBuildingTypes(): array
    {
        return array_keys(config('game.building_types', []));
    }

    protected function transformEntry(BuildingQueueEntry $entry): array
    {
        return [
            'id' => $entry->getKey(),
            'building' => $entry->building_key,
            'target_level' => (int) $entry->target_level,
            'slot' => (int) $entry->slot,
            'finishes_at' => optional($entry->finishes_at)->toIso8601String(),
            'cost' => $entry->cost ?? [],
        ];
    }
}
