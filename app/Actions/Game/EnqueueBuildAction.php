<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Enums\Game\BuildQueueState;
use App\Models\Game\Building;
use App\Models\Game\BuildQueue;
use App\Models\Game\ResourceField;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use App\Repositories\Game\BuildingQueueRepository;
use App\Repositories\Game\VillageRepository;
use App\Services\Game\BuildingService;
use App\Support\Auth\SitterPermissionMatrixResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class EnqueueBuildAction
{
    /**
     * Ordered Travian resource keys.
     *
     * @var array<int, string>
     */
    private const RESOURCE_KEYS = ['wood', 'clay', 'iron', 'crop'];

    /**
     * Mapping of resource field building types to their kinds.
     *
     * @var array<int, string>
     */
    private const RESOURCE_FIELD_MAPPING = [
        1 => 'wood',
        2 => 'clay',
        3 => 'iron',
        4 => 'crop',
    ];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $buildingDefinitions;

    public function __construct(
        private readonly VillageRepository $villages,
        private readonly BuildingQueueRepository $buildingQueues,
        ?array $buildingDefinitions = null,
    ) {
        $this->buildingDefinitions = $buildingDefinitions ?? require base_path('app/Data/buildings.php');
    }

    public function execute(Village $village, int $buildingType): BuildQueue
    {
        $owner = $this->resolveOwner($village);
        (new SitterPermissionMatrixResolver($owner))->assertAllowed('build');

        $definition = $this->getBuildingDefinition($buildingType);

        $this->ensureWorkerSlotAvailable($village);

        $currentLevel = $this->determineCurrentLevel($village, $buildingType);
        $queuedLevel = $this->determineQueuedTargetLevel($village, $buildingType);
        $effectiveLevel = max($currentLevel, $queuedLevel);
        $targetLevel = $effectiveLevel + 1;

        $maxLevel = $this->determineMaxLevel($village, $buildingType, $definition);
        if ($targetLevel > $maxLevel) {
            throw new RuntimeException("Building type {$buildingType} cannot exceed level {$maxLevel}.");
        }

        $this->ensurePrerequisitesMet($village, $definition, $buildingType, $targetLevel);

        $worldSpeed = $this->determineWorldSpeed($village);
        $mainBuildingLevel = $this->determineCurrentLevel($village, 15);

        $cost = $this->calculateBuildCost($definition, $buildingType, $targetLevel, $worldSpeed);
        $resourceBalances = $this->extractResourceBalances($village);

        foreach (self::RESOURCE_KEYS as $resource) {
            if (($resourceBalances[$resource] ?? 0) < ($cost[$resource] ?? 0)) {
                throw new RuntimeException("Insufficient {$resource} to enqueue building type {$buildingType}.");
            }
        }

        $buildingService = new BuildingService(gameSpeed: $worldSpeed);
        $duration = $buildingService->calculateBuildTime($buildingType, $targetLevel, $mainBuildingLevel);

        $finishesAt = Carbon::now()->addSeconds($duration);

        return DB::transaction(function () use ($village, $buildingType, $targetLevel, $cost, $resourceBalances, $finishesAt): BuildQueue {
            $updatedBalances = $this->reserveResources($resourceBalances, $cost);
            $village->resource_balances = $updatedBalances;
            $village->save();

            return BuildQueue::create([
                'village_id' => $village->getKey(),
                'building_type' => $buildingType,
                'target_level' => $targetLevel,
                'state' => BuildQueueState::Pending,
                'finishes_at' => $finishesAt,
            ]);
        });
    }

    private function resolveOwner(Village $village): User
    {
        $owner = $village->owner ?? $village->getRelationValue('owner');

        if ($owner instanceof User) {
            return $owner;
        }

        $owner = $village->owner()->first();

        if ($owner instanceof User) {
            return $owner;
        }

        throw new InvalidArgumentException('Unable to resolve the owner for the provided village.');
    }

    /**
     * @return array<string, mixed>
     */
    private function getBuildingDefinition(int $buildingType): array
    {
        $index = $buildingType - 1;

        if (! isset($this->buildingDefinitions[$index])) {
            throw new InvalidArgumentException("Unknown building type: {$buildingType}");
        }

        return $this->buildingDefinitions[$index];
    }

    private function determineCurrentLevel(Village $village, int $buildingType): int
    {
        if (isset(self::RESOURCE_FIELD_MAPPING[$buildingType])) {
            $kind = self::RESOURCE_FIELD_MAPPING[$buildingType];
            $level = ResourceField::query()
                ->where('village_id', $village->getKey())
                ->where('kind', $kind)
                ->max('level');

            return $level === null ? 0 : (int) $level;
        }

        $level = Building::query()
            ->where('village_id', $village->getKey())
            ->where('building_type', $buildingType)
            ->max('level');

        return $level === null ? 0 : (int) $level;
    }

    private function determineQueuedTargetLevel(Village $village, int $buildingType): int
    {
        $queued = BuildQueue::query()
            ->where('village_id', $village->getKey())
            ->where('building_type', $buildingType)
            ->whereIn('state', [BuildQueueState::Pending, BuildQueueState::Working])
            ->max('target_level');

        return $queued === null ? 0 : (int) $queued;
    }

    private function determineMaxLevel(Village $village, int $buildingType, array $definition): int
    {
        if ($buildingType === 40) {
            return 100;
        }

        if ($buildingType <= 4) {
            return $village->is_capital ? 21 : 10;
        }

        $maxLevel = (int) ($definition['maxLvl'] ?? 20);

        $capitalRequirement = (int) ($definition['req']['capital'] ?? 0);
        if ($capitalRequirement > 1 && ! $village->is_capital) {
            $maxLevel = min($maxLevel, $capitalRequirement);
        }

        return $maxLevel;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function ensurePrerequisitesMet(Village $village, array $definition, int $buildingType, int $targetLevel): void
    {
        $requirements = (array) ($definition['breq'] ?? []);

        foreach ($requirements as $requiredType => $requiredLevel) {
            $actualLevel = $this->determineCurrentLevel($village, (int) $requiredType);

            if ($actualLevel < (int) $requiredLevel) {
                throw new RuntimeException("Building type {$buildingType} requires building {$requiredType} at level {$requiredLevel}.");
            }
        }

        $flags = (array) ($definition['req'] ?? []);

        if (isset($flags['capital'])) {
            $capitalRequirement = (int) $flags['capital'];

            if ($capitalRequirement === 1 && ! $village->is_capital) {
                throw new RuntimeException('This building can only be constructed in a capital village.');
            }

            if ($capitalRequirement === -1 && $village->is_capital) {
                throw new RuntimeException('This building cannot be constructed in a capital village.');
            }

            if ($capitalRequirement > 1 && ! $village->is_capital && $targetLevel > $capitalRequirement) {
                throw new RuntimeException("Non-capital villages cannot upgrade this building beyond level {$capitalRequirement}.");
            }
        }
    }

    private function ensureWorkerSlotAvailable(Village $village): void
    {
        $activeWorkers = BuildQueue::query()
            ->where('village_id', $village->getKey())
            ->whereIn('state', [BuildQueueState::Pending, BuildQueueState::Working])
            ->count();

        if ($activeWorkers > 0) {
            throw new RuntimeException('All worker slots are currently occupied.');
        }
    }

    private function determineWorldSpeed(Village $village): float
    {
        $speed = (float) data_get($village, 'world.speed', 0.0);

        if ($speed <= 0.0) {
            $worldId = data_get($village, 'world_id');

            if ($worldId !== null) {
                $world = World::query()->find($worldId);
                $speed = $world?->speed ? (float) $world->speed : $speed;
            }
        }

        if ($speed <= 0.0) {
            $speed = (float) config('travian.settings.game.speed', 1.0);
        }

        return max(0.1, $speed);
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, int>
     */
    private function calculateBuildCost(array $definition, int $buildingType, int $targetLevel, float $worldSpeed): array
    {
        $baseCosts = (array) ($definition['cost'] ?? []);
        $multiplier = (float) ($definition['k'] ?? 1.0);

        $costs = [];
        foreach (self::RESOURCE_KEYS as $index => $resource) {
            $base = (float) ($baseCosts[$index] ?? 0);
            $value = round($base * pow($multiplier, $targetLevel - 1) / 5) * 5;

            if ($buildingType === 40) {
                if (($targetLevel === 100 && $index < 3) || $value > 1_000_000) {
                    $value = 1_000_000;
                }
            }

            $costs[$resource] = (int) round($value);
        }

        if ($buildingType <= 4 && $targetLevel > 20 && $worldSpeed > 10) {
            foreach (self::RESOURCE_KEYS as $resource) {
                $costs[$resource] = (int) round($costs[$resource] * 10);
            }
        }

        return $costs;
    }

    /**
     * @return array<string, int>
     */
    private function extractResourceBalances(Village $village): array
    {
        $balances = (array) ($village->resource_balances ?? []);

        $normalised = [];
        foreach (self::RESOURCE_KEYS as $resource) {
            $normalised[$resource] = (int) ($balances[$resource] ?? 0);
        }

        return $normalised;
    }

    /**
     * @param array<string, int> $balances
     * @param array<string, int> $cost
     * @return array<string, int>
     */
    private function reserveResources(array $balances, array $cost): array
    {
        foreach (self::RESOURCE_KEYS as $resource) {
            $balances[$resource] = (int) (($balances[$resource] ?? 0) - ($cost[$resource] ?? 0));
        }

        return $balances;
    }
}
