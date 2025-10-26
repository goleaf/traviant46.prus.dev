<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Models\Game\BuildingCatalog;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageResource;
use Illuminate\Support\Collection;

class BuildingPrerequisiteResolver
{
    /**
     * Mapping of resource field slugs to resource balance keys.
     *
     * @var array<string, string>
     */
    private const RESOURCE_SLUG_MAP = [
        'woodcutter' => 'wood',
        'clay-pit' => 'clay',
        'iron-mine' => 'iron',
        'cropland' => 'crop',
    ];

    /**
     * Resolve prerequisite requirements for a given building catalog entry.
     *
     * @param array{
     *     effective_building_levels?: array<string, int>,
     *     resource_levels?: array<string, int>
     * } $context
     * @return array<int, array{
     *     type: string,
     *     slug: string,
     *     name: string,
     *     required_level: int,
     *     current_level: int,
     *     met: bool,
     *     summary: string
     * }>
     */
    public function resolve(BuildingCatalog $catalog, array $context = []): array
    {
        $prerequisites = $catalog->prerequisites ?? [];

        if (! is_array($prerequisites) || $prerequisites === []) {
            return [];
        }

        $effectiveLevels = $context['effective_building_levels'] ?? [];
        $resourceLevels = $context['resource_levels'] ?? [];

        return collect($prerequisites)
            ->filter(static fn ($requirement): bool => is_array($requirement))
            ->map(function (array $requirement) use ($effectiveLevels, $resourceLevels): array {
                $type = (string) ($requirement['type'] ?? 'building');
                $slug = (string) ($requirement['slug'] ?? '');
                $name = (string) ($requirement['name'] ?? $slug);
                $requiredLevel = max(0, (int) ($requirement['level'] ?? 0));

                $currentLevel = $this->resolveCurrentLevel($type, $slug, $effectiveLevels, $resourceLevels);
                $met = $currentLevel >= $requiredLevel;

                return [
                    'type' => $type,
                    'slug' => $slug,
                    'name' => $name,
                    'required_level' => $requiredLevel,
                    'current_level' => $currentLevel,
                    'met' => $met,
                    'summary' => __('Requires :name :level', [
                        'name' => $name,
                        'level' => $requiredLevel,
                    ]),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Resolve prerequisites for a village, calculating current building and resource levels.
     *
     * @param array{
     *     queued_targets_by_slot?: array<int, int>,
     *     effective_building_levels?: array<string, int>,
     *     resource_levels?: array<string, int>
     * } $context
     * @return array<int, array{
     *     type: string,
     *     slug: string,
     *     name: string,
     *     required_level: int,
     *     current_level: int,
     *     met: bool,
     *     summary: string
     * }>
     */
    public function resolveForVillage(Village $village, BuildingCatalog $catalog, array $context = []): array
    {
        $effectiveLevels = $context['effective_building_levels']
            ?? $this->determineEffectiveBuildingLevels(
                $village,
                $context['queued_targets_by_slot'] ?? [],
            );

        $resourceLevels = $context['resource_levels'] ?? $this->determineResourceLevels($village);

        return $this->resolve($catalog, [
            'effective_building_levels' => $effectiveLevels,
            'resource_levels' => $resourceLevels,
        ]);
    }

    /**
     * @param array<string, int> $effectiveBuildingLevels
     * @param array<string, int> $resourceLevels
     */
    private function resolveCurrentLevel(string $type, string $slug, array $effectiveBuildingLevels, array $resourceLevels): int
    {
        if ($type === 'resource-field') {
            $resourceKey = self::RESOURCE_SLUG_MAP[$slug] ?? $slug;

            return $resourceLevels[$resourceKey] ?? 0;
        }

        return $effectiveBuildingLevels[$slug] ?? 0;
    }

    /**
     * @param array<int, int> $queuedTargetsBySlot
     * @return array<string, int>
     */
    private function determineEffectiveBuildingLevels(Village $village, array $queuedTargetsBySlot = []): array
    {
        $village->loadMissing('buildings.buildable');

        /** @var Collection<int, VillageBuilding> $buildings */
        $buildings = $village->buildings
            ->filter(static fn (VillageBuilding $building): bool => $building->buildable !== null)
            ->sortBy('slot_number')
            ->values();

        $levels = [];

        $buildings->each(function (VillageBuilding $building) use (&$levels, $queuedTargetsBySlot): void {
            $slug = (string) ($building->buildable?->slug ?? '');

            if ($slug === '') {
                return;
            }

            $queuedTarget = $queuedTargetsBySlot[$building->slot_number] ?? null;
            $effectiveLevel = max((int) $building->level, $queuedTarget ?? 0);

            $levels[$slug] = max($levels[$slug] ?? 0, $effectiveLevel);
        });

        return $levels;
    }

    /**
     * @return array<string, int>
     */
    private function determineResourceLevels(Village $village): array
    {
        $village->loadMissing('resources');

        /** @var Collection<int, VillageResource> $resources */
        $resources = $village->resources;

        $levels = [];

        $resources->each(function (VillageResource $resource) use (&$levels): void {
            $type = (string) $resource->resource_type;
            $level = (int) $resource->level;

            $levels[$type] = max($levels[$type] ?? 0, $level);
        });

        return $levels;
    }
}
