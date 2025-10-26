<?php

declare(strict_types=1);

namespace App\Repositories\Game;

use App\Models\Game\Village;
use App\Services\Game\MapDistanceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VillageRepository
{
    public function __construct(
        private readonly MapDistanceService $distances,
    ) {}

    public function find(int $villageId): ?Village
    {
        return Village::query()->find($villageId);
    }

    public function findByCoordinates(int $x, int $y): ?Village
    {
        return Village::query()->byCoordinates($x, $y)->first();
    }

    /**
     * @param array<int, string> $with
     */
    public function lock(int|Village $village, array $with = []): Village
    {
        $villageId = $village instanceof Village ? $village->getKey() : $village;

        $query = Village::query()
            ->whereKey($villageId)
            ->lockForUpdate();

        if ($with !== []) {
            $query->with($with);
        }

        $locked = $query->first();

        if ($locked === null) {
            throw new ModelNotFoundException('Village not found.');
        }

        return $locked;
    }

    public function channelFor(int|Village $village): string
    {
        $id = $village instanceof Village ? (int) $village->getKey() : (int) $village;

        return sprintf('game.village.%d', max(0, $id));
    }

    public function wrappedDistance(Village $origin, Village $target): float
    {
        return $this->distances->distanceBetweenVillages($origin, $target);
    }

    public function buildingLevel(Village $village, int $buildingType): int
    {
        $level = DB::table('buildings')
            ->where('village_id', $village->getKey())
            ->where('building_type', $buildingType)
            ->max('level');

        return $level !== null ? (int) $level : 0;
    }

    public function hasBuildingAtLeast(Village $village, int $buildingType, int $level): bool
    {
        return $this->buildingLevel($village, $buildingType) >= $level;
    }

    /**
     * @param array<int, array<string, mixed>> $options
     *
     * @return array<string, int|string>|null
     */
    public function resolveTrainingOption(Village $village, array $options): ?array
    {
        foreach ($options as $option) {
            $buildingId = (int) ($option['building_id'] ?? 0);
            if ($buildingId === 0) {
                continue;
            }

            $requiredLevel = max(1, (int) ($option['level'] ?? 1));
            $actualLevel = $this->buildingLevel($village, $buildingId);

            if ($actualLevel < $requiredLevel) {
                continue;
            }

            $reference = (string) ($option['ref'] ?? ('building_'.$buildingId));

            return [
                'building_id' => $buildingId,
                'required_level' => $requiredLevel,
                'actual_level' => $actualLevel,
                'ref' => $reference,
            ];
        }

        return null;
    }

    /**
     * @param array<int, array<string, int>> $requirements
     */
    public function meetAllRequirements(Village $village, array $requirements): bool
    {
        foreach ($requirements as $requirement) {
            $buildingId = (int) ($requirement['building_id'] ?? 0);
            if ($buildingId === 0) {
                continue;
            }

            $level = max(1, (int) ($requirement['level'] ?? 1));

            if (! $this->hasBuildingAtLeast($village, $buildingId, $level)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, int> $cost
     */
    public function hasResources(Village $village, array $cost): bool
    {
        $balances = $this->normaliseResourceBalances($village->resource_balances ?? []);

        foreach ($cost as $resource => $amount) {
            if ($amount < 0) {
                continue;
            }

            if ($balances[$resource] < $amount) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, int> $cost
     */
    public function deductResources(Village $village, array $cost): Village
    {
        if (! $this->hasResources($village, $cost)) {
            throw new InvalidArgumentException('Village does not have enough resources.');
        }

        $balances = $this->normaliseResourceBalances($village->resource_balances ?? []);

        foreach ($cost as $resource => $amount) {
            $balances[$resource] -= $amount;
        }

        $village->resource_balances = $balances;
        $village->save();

        return $village->refresh();
    }

    /**
     * @param array<string, int|float|null> $balances
     *
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
}
