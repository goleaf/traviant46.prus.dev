<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Models\Game\Village;
use App\Models\Game\VillageResource;
use App\Services\ResourceService;
use Illuminate\Support\Carbon;

/**
 * Aggregate-oriented helper for the village resource overview (legacy Dorf1Ctrl).
 *
 * Responsibilities inherited from the legacy controller:
 * - expose per-hour production that combines base village output with resource
 *   field contributions and percentage/flat bonuses.
 * - surface storage capacities so the UI can highlight impending caps.
 * - provide a deterministic snapshot that downstream Livewire components can
 *   cache and diff during polling.
 */
class VillageResourceService
{
    /**
     * Ordered list of the supported Travian resources.
     *
     * @var array<int, string>
     */
    private const RESOURCE_KEYS = ['wood', 'clay', 'iron', 'crop'];

    public function __construct(
        private readonly ResourceService $resourceCalculator,
        private readonly VillageUpkeepService $upkeepService,
    ) {}

    /**
     * Build a resource snapshot for the given village.
     *
     * @return array{
     *     village_id: int,
     *     base_production: array<string, float>,
     *     field_production: array<string, float>,
     *     bonuses: array{
     *         percent: array<string, float>,
     *         flat: array<string, float>,
     *         upkeep: array<string, float>
     *     },
     *     production: array<string, float>,
     *     storage: array<string, float>,
     *     generated_at: string
     * }
     */
    public function snapshot(Village $village): array
    {
        $village->loadMissing('resources');

        $baseProduction = $this->extractBaseProduction($village);
        $fieldProduction = $this->aggregateFieldProduction($village);
        $bonuses = $this->aggregateBonuses($village);
        $troopUpkeep = $this->upkeepService->calculate($village);

        $combinedBase = $this->mergeProduction($baseProduction, $fieldProduction);

        $upkeepMap = $bonuses['upkeep'];
        $cropKey = 'crop';

        $buildingUpkeepPerHour = (float) ($upkeepMap[$cropKey] ?? 0);
        $totalTroopUpkeepPerHour = (float) ($troopUpkeep['per_hour'] ?? 0);
        $tickInterval = (float) config('game.tick_interval_seconds', 60);
        $secondsFactor = $tickInterval / 3600;

        $upkeepMap[$cropKey] = $buildingUpkeepPerHour + $totalTroopUpkeepPerHour;

        $production = $this->resourceCalculator->calculateProduction(
            $combinedBase,
            [
                'percent' => $bonuses['percent'],
                'flat' => $bonuses['flat'],
                'upkeep' => $upkeepMap,
                'options' => [
                    'minimum' => 0,
                    'allow_negative_crop' => true,
                ],
            ],
        );

        $upkeepSummary = [
            'resource' => $cropKey,
            'per_hour' => $upkeepMap[$cropKey],
            'per_tick' => $upkeepMap[$cropKey] * $secondsFactor,
            'sources' => [
                'buildings' => [
                    'per_hour' => $buildingUpkeepPerHour,
                    'per_tick' => $buildingUpkeepPerHour * $secondsFactor,
                ],
                'troops' => $troopUpkeep,
            ],
        ];

        return [
            'village_id' => (int) $village->getKey(),
            'base_production' => $baseProduction,
            'field_production' => $fieldProduction,
            'bonuses' => $bonuses,
            'production' => array_map('floatval', $production),
            'storage' => $this->aggregateStorage($village),
            'upkeep' => $upkeepSummary,
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, float>
     */
    private function extractBaseProduction(Village $village): array
    {
        $source = $village->production;
        $values = $this->blankResourceMap();

        if (is_array($source)) {
            foreach ($source as $resource => $value) {
                if ($this->isKnownResource($resource) && is_numeric($value)) {
                    $values[$resource] += (float) $value;
                }
            }
        }

        return $values;
    }

    /**
     * @return array<string, float>
     */
    private function aggregateFieldProduction(Village $village): array
    {
        $values = $this->blankResourceMap();

        /** @var VillageResource $resource */
        foreach ($village->resources as $resource) {
            $type = $resource->resource_type;
            if (! $this->isKnownResource($type)) {
                continue;
            }

            $values[$type] += (float) ($resource->production_per_hour ?? 0);
        }

        return $values;
    }

    /**
     * @return array{
     *     percent: array<string, float>,
     *     flat: array<string, float>,
     *     upkeep: array<string, float>
     * }
     */
    private function aggregateBonuses(Village $village): array
    {
        $percent = $this->blankResourceMap();
        $flat = $this->blankResourceMap();
        $upkeep = $this->blankResourceMap();

        /** @var VillageResource $resource */
        foreach ($village->resources as $resource) {
            $type = $resource->resource_type;
            if (! $this->isKnownResource($type)) {
                continue;
            }

            $bonuses = (array) ($resource->bonuses ?? []);

            $percent = $this->applyModifier($percent, $type, $bonuses['percent'] ?? null);
            $flat = $this->applyModifier($flat, $type, $bonuses['flat'] ?? null);
            $upkeep = $this->applyModifier($upkeep, $type, $bonuses['upkeep'] ?? null);
        }

        return [
            'percent' => $percent,
            'flat' => $flat,
            'upkeep' => $upkeep,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function aggregateStorage(Village $village): array
    {
        $storage = $this->blankResourceMap();

        $baseStorage = $village->storage;
        if (is_array($baseStorage)) {
            foreach ($baseStorage as $resource => $value) {
                if ($this->isKnownResource($resource) && is_numeric($value)) {
                    $storage[$resource] += (float) $value;
                }
            }
        }

        /** @var VillageResource $resource */
        foreach ($village->resources as $resource) {
            $type = $resource->resource_type;
            if (! $this->isKnownResource($type)) {
                continue;
            }

            $storage[$type] += (float) ($resource->storage_capacity ?? 0);
        }

        return $storage;
    }

    /**
     * @param array<string, float> ...$parts
     * @return array<string, float>
     */
    private function mergeProduction(array ...$parts): array
    {
        $merged = $this->blankResourceMap();

        foreach ($parts as $part) {
            foreach ($part as $resource => $value) {
                if (! $this->isKnownResource($resource) || ! is_numeric($value)) {
                    continue;
                }

                $merged[$resource] += (float) $value;
            }
        }

        return $merged;
    }

    /**
     * @param array<string, float> $map
     * @return array<string, float>
     */
    private function applyModifier(array $map, string $resourceType, mixed $modifier): array
    {
        if ($modifier === null) {
            return $map;
        }

        if (is_numeric($modifier)) {
            $map[$resourceType] += (float) $modifier;

            return $map;
        }

        if (is_array($modifier)) {
            foreach ($modifier as $key => $value) {
                if (! is_numeric($value)) {
                    continue;
                }

                if ($this->isKnownResource((string) $key)) {
                    $map[(string) $key] += (float) $value;

                    continue;
                }

                if (in_array($key, ['all', '*', 'global'], true)) {
                    foreach (self::RESOURCE_KEYS as $resource) {
                        $map[$resource] += (float) $value;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * @return array<string, float>
     */
    private function blankResourceMap(): array
    {
        return array_fill_keys(self::RESOURCE_KEYS, 0.0);
    }

    private function isKnownResource(?string $resource): bool
    {
        if ($resource === null) {
            return false;
        }

        return in_array($resource, self::RESOURCE_KEYS, true);
    }
}
