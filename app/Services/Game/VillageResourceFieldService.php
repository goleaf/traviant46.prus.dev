<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Enums\Game\BuildQueueState;
use App\Models\Game\BuildQueue;
use App\Models\Game\ResourceField;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class VillageResourceFieldService
{
    private const KIND_TO_GID = [
        'wood' => 1,
        'clay' => 2,
        'iron' => 3,
        'crop' => 4,
    ];

    private const KIND_LABELS = [
        'wood' => 'Woodcutter',
        'clay' => 'Clay pit',
        'iron' => 'Iron mine',
        'crop' => 'Cropland',
    ];

    private const MAX_QUEUE_ENTRIES = 1;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $definitionsByGid;

    public function __construct(
        private readonly BuildingService $buildingService,
    ) {
        $this->definitionsByGid = $this->indexDefinitions(
            require base_path('app/Data/buildings.php'),
        );
    }

    /**
     * Build a snapshot of the village resource fields enriched with upgrade metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    public function snapshot(Village $village): array
    {
        $buildQueueEntries = $this->activeBuildQueueEntries($village);
        $activeQueueCount = $buildQueueEntries->count();
        $queueByType = $buildQueueEntries->groupBy(
            static fn (BuildQueue $entry) => (int) $entry->building_type,
        );

        /** @var EloquentCollection<int, ResourceField> $resourceFields */
        $resourceFields = $village->resourceFields instanceof EloquentCollection
            ? $village->resourceFields
            : $village->resourceFields()->get();

        $mainBuildingLevel = $this->resolveMainBuildingLevel($village);

        return $resourceFields
            ->sortBy('slot_number')
            ->values()
            ->map(function (ResourceField $field) use ($village, $queueByType, $activeQueueCount, $mainBuildingLevel): array {
                $kind = (string) $field->kind;
                $definition = $this->definitionForKind($kind);
                $gid = $definition['gid'] ?? null;
                $name = $definition['name'] ?? self::KIND_LABELS[$kind] ?? Str::title($kind);

                $queuedForType = $gid !== null
                    ? $queueByType->get((int) $gid, collect())
                    : collect();

                $hasQueued = $queuedForType instanceof Collection && $queuedForType->isNotEmpty();
                $queuedLevel = $hasQueued ? (int) $queuedForType->max('target_level') : null;
                $firstState = $hasQueued ? $queuedForType->first()->state : null;
                $queueStatus = $firstState instanceof BuildQueueState
                    ? $firstState->value
                    : (is_string($firstState) ? $firstState : null);

                [$isLocked, $lockedReasons] = $this->determineLocks(
                    $village,
                    $field,
                    $definition,
                    $activeQueueCount,
                    $hasQueued,
                );

                $durationSeconds = $this->calculateUpgradeDuration(
                    $gid,
                    $field,
                    $mainBuildingLevel,
                    $village,
                    $isLocked,
                );

                return [
                    'id' => (int) $field->getKey(),
                    'slot' => (int) $field->slot_number,
                    'kind' => $kind,
                    'name' => $name,
                    'building_type' => $gid !== null ? (int) $gid : null,
                    'level' => (int) $field->level,
                    'next_level' => (int) $field->level + 1,
                    'queued_level' => $queuedLevel,
                    'queue_status' => $queueStatus,
                    'is_locked' => $isLocked,
                    'locked_reasons' => $lockedReasons,
                    'duration_seconds' => $durationSeconds,
                    'duration_formatted' => $durationSeconds !== null
                        ? $this->formatDuration($durationSeconds)
                        : null,
                    'production_per_hour' => (int) $field->production_per_hour_cached,
                ];
            })
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     * @return array<int, array<string, mixed>>
     */
    private function indexDefinitions(array $definitions): array
    {
        $indexed = [];

        foreach ($definitions as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $gid = $definition['gid'] ?? null;

            if ($gid === null) {
                continue;
            }

            $indexed[(int) $gid] = $definition;
        }

        return $indexed;
    }

    private function definitionForKind(string $kind): array
    {
        $gid = self::KIND_TO_GID[$kind] ?? null;

        if ($gid === null) {
            return [];
        }

        return $this->definitionsByGid[$gid] ?? [];
    }

    private function resolveMainBuildingLevel(Village $village): int
    {
        $buildings = $village->buildings instanceof EloquentCollection
            ? $village->buildings
            : $village->buildings()->get();

        $mainBuilding = $buildings->firstWhere('building_type', 15);

        return $mainBuilding !== null ? (int) $mainBuilding->level : 0;
    }

    private function activeBuildQueueEntries(Village $village): Collection
    {
        return BuildQueue::query()
            ->where('village_id', $village->getKey())
            ->whereIn('state', ['pending', 'working'])
            ->get();
    }

    /**
     * @return array{0: bool, 1: array<int, string>}
     */
    private function determineLocks(
        Village $village,
        ResourceField $field,
        array $definition,
        int $activeQueueCount,
        bool $alreadyQueued
    ): array {
        $reasons = [];
        $isLocked = false;

        $maxLevel = isset($definition['maxLvl']) ? (int) $definition['maxLvl'] : 20;
        $capitalRequirement = (int) ($definition['req']['capital'] ?? $maxLevel);
        $capitalCap = min($capitalRequirement, $maxLevel);
        $villageCap = $village->is_capital ? $maxLevel : $capitalCap;

        $name = $definition['name'] ?? self::KIND_LABELS[$field->kind] ?? Str::title((string) $field->kind);
        $currentLevel = (int) $field->level;

        if ($currentLevel >= $villageCap) {
            $isLocked = true;

            if (! $village->is_capital && $capitalCap < $maxLevel && $currentLevel >= $capitalCap) {
                $reasons[] = __('Only capital villages can upgrade :name beyond level :level.', [
                    'name' => $name,
                    'level' => $capitalCap,
                ]);
            } else {
                $reasons[] = __(':name has reached level :level.', [
                    'name' => $name,
                    'level' => $currentLevel,
                ]);
            }
        }

        if ($activeQueueCount >= self::MAX_QUEUE_ENTRIES && ! $alreadyQueued) {
            $isLocked = true;
            $reasons[] = __('Construction queue is full.');
        }

        return [$isLocked, array_values(array_unique($reasons))];
    }

    private function calculateUpgradeDuration(
        mixed $gid,
        ResourceField $field,
        int $mainBuildingLevel,
        Village $village,
        bool $locked
    ): ?int {
        if ($locked || $gid === null) {
            return null;
        }

        try {
            return $this->buildingService->calculateBuildTime(
                (int) $gid,
                (int) $field->level + 1,
                $mainBuildingLevel,
                (bool) $village->is_wonder_village,
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }
}
