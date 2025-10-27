<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Enums\Game\BuildQueueState;
use App\Models\Game\BuildQueue;
use App\Models\Game\Village;
use App\Repositories\Game\BuildingQueueRepository;
use App\Repositories\Game\VillageRepository;
use App\Services\Game\BuildingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Prepare building upgrade requests by dispatching them to the queue repository.
 */
class EnqueueBuildAction
{
    /**
     * Inject repositories to coordinate village data and the building queue.
     */
    public function __construct(
        private VillageRepository $villageRepository,
        private BuildingQueueRepository $buildingQueueRepository,
    ) {
    }

    /**
     * Execute the action by adding a building request to the queue.
     */
    public function execute(Village $village, int $buildingType): BuildQueue
    {
        $definition = $this->resolveDefinition($buildingType);
        $this->assertWorkerSlotsAvailable($village);
        $this->assertPrerequisitesMet($village, $definition);

        $queuedLevel = $this->latestQueuedLevel($village, $buildingType);
        $currentLevel = $this->resolveExistingLevel($village, $buildingType);
        $effectiveLevel = max($currentLevel, $queuedLevel);
        $targetLevel = $effectiveLevel + 1;

        $this->assertTargetWithinBounds($village, $definition, $targetLevel);

        $worldSpeed = (float) config('travian.settings.game.speed', 1.0);
        $cost = $this->calculateUpgradeCost($definition, $buildingType, $targetLevel, $worldSpeed);

        if (! $this->hasResources($village, $cost)) {
            throw new RuntimeException('Village does not have enough resources to start construction.');
        }

        $mainBuildingLevel = $this->buildingLevel($village, 15);
        $isWorldWonder = (int) $buildingType === 40;

        $buildingService = new BuildingService(gameSpeed: $worldSpeed);
        $duration = $buildingService->calculateBuildTime(
            $buildingType,
            $targetLevel,
            $mainBuildingLevel,
            $isWorldWonder,
        );

        $finishesAt = Carbon::now()->addSeconds($duration);

        /** @var BuildQueue $queued */
        $queued = DB::transaction(function () use ($village, $buildingType, $targetLevel, $finishesAt, $cost): BuildQueue {
            $this->deductResources($village, $cost);

            return $this->createQueueEntry(
                $village->refresh(),
                $buildingType,
                $targetLevel,
                $finishesAt,
            );
        });

        return $queued;
    }

    /**
     * Resolve the building definition for the provided identifier.
     *
     * @return array<string, mixed>
     */
    private function resolveDefinition(int $buildingType): array
    {
        static $definitionsByGid = null;

        if ($definitionsByGid === null) {
            $definitions = require base_path('app/Data/buildings.php');

            $definitionsByGid = [];

            foreach ($definitions as $definition) {
                if (! is_array($definition)) {
                    continue;
                }

                $gid = $definition['gid'] ?? null;

                if ($gid === null) {
                    continue;
                }

                $definitionsByGid[(int) $gid] = $definition;
            }
        }

        $definition = $definitionsByGid[$buildingType] ?? null;

        if (! is_array($definition)) {
            throw new InvalidArgumentException('Unknown building type provided.');
        }

        return $definition;
    }

    /**
     * Ensure the village still has free worker slots to start a new construction.
     */
    private function assertWorkerSlotsAvailable(Village $village): void
    {
        $active = $this->activeSlotCount($village);
        $limit = max(1, (int) config('travian.settings.game.builder_slots', 1));

        if ($active >= $limit) {
            throw new RuntimeException('All worker slots are currently occupied.');
        }
    }

    /**
     * Validate the prerequisites declared in the building definition.
     *
     * @param array<string, mixed> $definition
     */
    private function assertPrerequisitesMet(Village $village, array $definition): void
    {
        /** @var array<int, int>|null $requirements */
        $requirements = $definition['breq'] ?? null;

        if ($requirements === null || $requirements === []) {
            return;
        }

        foreach ($requirements as $requiredType => $requiredLevel) {
            $requiredType = (int) $requiredType;
            $requiredLevel = max(1, (int) $requiredLevel);

            $actualLevel = $requiredType <= 4
                ? $this->resolveResourceFieldLevel($village, $requiredType)
                : $this->buildingLevel($village, $requiredType);

            if ($actualLevel < $requiredLevel) {
                throw new RuntimeException('Village does not meet the required building prerequisites.');
            }
        }
    }

    /**
     * Ensure the target level remains within the definition constraints.
     *
     * @param array<string, mixed> $definition
     */
    private function assertTargetWithinBounds(Village $village, array $definition, int $targetLevel): void
    {
        $maxLevel = (int) ($definition['maxLvl'] ?? $targetLevel);
        $capitalCap = isset($definition['req']['capital'])
            ? (int) $definition['req']['capital']
            : $maxLevel;

        if (! (bool) $village->is_capital) {
            $maxLevel = min($maxLevel, $capitalCap);
        }

        if ($targetLevel > $maxLevel) {
            throw new RuntimeException('Requested building level exceeds the allowed maximum.');
        }
    }

    /**
     * Determine the existing building level stored in the village structures.
     */
    private function resolveExistingLevel(Village $village, int $buildingType): int
    {
        if ($buildingType <= 4) {
            return $this->resolveResourceFieldLevel($village, $buildingType);
        }

        return $this->buildingLevel($village, $buildingType);
    }

    /**
     * Determine the stored level for a specific building type within the village.
     */
    private function buildingLevel(Village $village, int $buildingType): int
    {
        if (method_exists($this->villageRepository, 'buildingLevel')) {
            return $this->villageRepository->buildingLevel($village, $buildingType);
        }

        $level = DB::table('buildings')
            ->where('village_id', $village->getKey())
            ->where('building_type', $buildingType)
            ->max('level');

        return $level !== null ? (int) $level : 0;
    }

    /**
     * Verify whether the village has enough resources to cover the provided cost.
     *
     * @param array<string, int> $cost
     */
    private function hasResources(Village $village, array $cost): bool
    {
        if (method_exists($this->villageRepository, 'hasResources')) {
            return $this->villageRepository->hasResources($village, $cost);
        }

        $balances = $this->normaliseResourceBalances($village->resource_balances ?? []);

        foreach ($cost as $resource => $amount) {
            if ($balances[$resource] < $amount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deduct the resource cost from the village inventory.
     *
     * @param array<string, int> $cost
     */
    private function deductResources(Village $village, array $cost): void
    {
        if (method_exists($this->villageRepository, 'deductResources')) {
            $this->villageRepository->deductResources($village, $cost);

            return;
        }

        $balances = $this->normaliseResourceBalances($village->resource_balances ?? []);

        foreach ($cost as $resource => $amount) {
            $balances[$resource] -= $amount;
        }

        $village->resource_balances = $balances;
        $village->save();
    }

    /**
     * Ensure resource arrays always contain each resource key.
     *
     * @param array<string, int|float|null> $balances
     * @return array<string, int>
     */
    private function normaliseResourceBalances(array $balances): array
    {
        $resources = ['wood', 'clay', 'iron', 'crop'];

        $normalised = [];

        foreach ($resources as $resource) {
            $normalised[$resource] = max(0, (int) ($balances[$resource] ?? 0));
        }

        return $normalised;
    }

    /**
     * Determine how many build queue slots are currently in use.
     */
    private function activeSlotCount(Village $village): int
    {
        if (method_exists($this->buildingQueueRepository, 'activeSlotCount')) {
            return $this->buildingQueueRepository->activeSlotCount($village);
        }

        return BuildQueue::query()
            ->where('village_id', $village->getKey())
            ->whereIn('state', ['pending', 'working'])
            ->count();
    }

    /**
     * Resolve the latest queued level for the provided building type.
     */
    private function latestQueuedLevel(Village $village, int $buildingType): int
    {
        if (method_exists($this->buildingQueueRepository, 'latestQueuedLevel')) {
            return $this->buildingQueueRepository->latestQueuedLevel($village, $buildingType);
        }

        $latest = BuildQueue::query()
            ->where('village_id', $village->getKey())
            ->where('building_type', $buildingType)
            ->whereIn('state', ['pending', 'working'])
            ->max('target_level');

        return $latest !== null ? (int) $latest : 0;
    }

    /**
     * Persist a build queue entry, falling back to the model if the repository lacks an implementation.
     */
    private function createQueueEntry(
        Village $village,
        int $buildingType,
        int $targetLevel,
        Carbon $finishesAt
    ): BuildQueue {
        if (method_exists($this->buildingQueueRepository, 'create')) {
            return $this->buildingQueueRepository->create($village, $buildingType, $targetLevel, $finishesAt);
        }

        return BuildQueue::query()->create([
            'village_id' => $village->getKey(),
            'building_type' => $buildingType,
            'target_level' => $targetLevel,
            'state' => BuildQueueState::Pending,
            'finishes_at' => $finishesAt,
        ]);
    }

    /**
     * Calculate the upgrade cost for the target level.
     *
     * @param array<string, mixed> $definition
     * @return array<string, int>
     */
    private function calculateUpgradeCost(array $definition, int $buildingType, int $targetLevel, float $worldSpeed): array
    {
        $baseCosts = (array) ($definition['cost'] ?? []);
        $multiplier = (float) ($definition['k'] ?? 1.0);
        $resources = ['wood', 'clay', 'iron', 'crop'];

        $costs = [];

        foreach ($resources as $index => $resource) {
            $base = (float) ($baseCosts[$index] ?? 0.0);
            $value = $base * pow($multiplier, max(0, $targetLevel - 1));
            $value = round($value / 5) * 5;

            if ($buildingType === 40) {
                if (($targetLevel === 100 && $index < 3) || $value > 1_000_000) {
                    $value = 1_000_000;
                }
            }

            $costs[$resource] = (int) round($value);
        }

        if ($buildingType <= 4 && $targetLevel > 20 && $worldSpeed > 10) {
            foreach ($costs as $resource => $value) {
                $costs[$resource] = (int) round($value * 10);
            }
        }

        return $costs;
    }

    /**
     * Resolve the highest level for resource fields matching the specified gid.
     */
    private function resolveResourceFieldLevel(Village $village, int $gid): int
    {
        $mapping = [
            1 => 'wood',
            2 => 'clay',
            3 => 'iron',
            4 => 'crop',
        ];

        $kind = $mapping[$gid] ?? null;

        if ($kind === null) {
            return 0;
        }

        $level = DB::table('resource_fields')
            ->where('village_id', $village->getKey())
            ->where('kind', $kind)
            ->max('level');

        return $level !== null ? (int) $level : 0;
    }
}
