<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Enums\Game\VillageBuildingUpgradeStatus;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageBuildingUpgrade;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BuildingService
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $buildingDefinitions;

    private float $gameSpeed;

    private bool $allowResourceOverflow;

    public function __construct(?array $buildingDefinitions = null, float $gameSpeed = 1.0, bool $allowResourceOverflow = false)
    {
        $this->buildingDefinitions = $buildingDefinitions ?? require base_path('app/Data/buildings.php');
        $this->gameSpeed = $gameSpeed;
        $this->allowResourceOverflow = $allowResourceOverflow;
    }

    public function canUpgrade(Village $village, VillageBuilding $building, int $levels = 1): bool
    {
        if ($levels <= 0) {
            return false;
        }

        if ($building->building_type === null) {
            return false;
        }

        $maxLevel = $this->buildingMaxLevel((int) $building->building_type, (bool) $village->is_capital);

        $lastTargetLevel = $this->determineLatestTargetLevel($village, $building);
        $effectiveLevel = max($building->level, $lastTargetLevel);
        $targetLevel = $effectiveLevel + $levels;

        if ($targetLevel > $maxLevel) {
            return false;
        }

        return true;
    }

    public function upgrade(
        Village $village,
        VillageBuilding $building,
        int $levels = 1,
        array $context = []
    ): VillageBuildingUpgrade {
        if (! $this->canUpgrade($village, $building, $levels)) {
            throw new InvalidArgumentException('Building cannot be upgraded to the requested level.');
        }

        $mainBuildingLevel = (int) ($context['main_building_level'] ?? 0);
        $isWorldWonder = (bool) ($context['is_world_wonder'] ?? false);

        $queuedLevels = $this->determineLatestTargetLevel($village, $building);
        $effectiveLevel = max($building->level, $queuedLevels);

        $targetLevel = $effectiveLevel + $levels;
        $maxLevel = $this->buildingMaxLevel((int) $building->building_type, (bool) $village->is_capital);
        $targetLevel = min($targetLevel, $maxLevel);

        $lastUpgrade = $this->latestUpgrade($village, $building);
        $startsAt = $lastUpgrade?->completes_at?->copy() ?? Carbon::now();

        $segments = [];
        $totalDuration = 0;
        for ($level = $effectiveLevel + 1; $level <= $targetLevel; $level++) {
            $duration = $this->calculateBuildTime((int) $building->building_type, $level, $mainBuildingLevel, $isWorldWonder);
            $segments[] = [
                'level' => $level,
                'duration' => $duration,
                'cost' => $this->calculateUpgradeCosts((int) $building->building_type, $level),
            ];
            $totalDuration += $duration;
        }

        $completesAt = $startsAt->copy()->addSeconds($totalDuration);

        $queuePosition = $this->nextQueuePosition($village);

        $upgrade = null;

        DB::transaction(function () use (
            $village,
            $building,
            $targetLevel,
            $startsAt,
            $completesAt,
            $queuePosition,
            $segments,
            &$upgrade
        ): void {
            $upgrade = VillageBuildingUpgrade::create([
                'village_id' => $village->getKey(),
                'village_building_id' => $building->getKey(),
                'slot_number' => $building->slot_number,
                'building_type' => (int) $building->building_type,
                'current_level' => $building->level,
                'target_level' => $targetLevel,
                'queue_position' => $queuePosition,
                'status' => VillageBuildingUpgradeStatus::Pending,
                'metadata' => [
                    'segments' => $segments,
                ],
                'queued_at' => Carbon::now(),
                'starts_at' => $startsAt,
                'completes_at' => $completesAt,
            ]);
        });

        return $upgrade;
    }

    public function demolish(Village $village, VillageBuilding $building, int $levels = 1): VillageBuilding
    {
        if ($levels <= 0) {
            return $building;
        }

        $pending = $village->buildingUpgrades()
            ->where('slot_number', $building->slot_number)
            ->whereIn('status', [
                VillageBuildingUpgradeStatus::Pending,
                VillageBuildingUpgradeStatus::Processing,
            ])
            ->count();

        if ($pending > 0) {
            throw new InvalidArgumentException('Cannot demolish a building with pending upgrades.');
        }

        $newLevel = max(0, $building->level - $levels);

        $building->level = $newLevel;
        $building->save();

        return $building;
    }

    public function calculateBuildTime(int $buildingType, int $level, int $mainBuildingLevel, bool $isWorldWonder = false): int
    {
        $definition = $this->getBuildingDefinition($buildingType);
        $time = array_values($definition['time']);

        if (count($time) < 3) {
            $time[1] = 1.16;
            if (count($time) === 1) {
                $time[1] = 1;
            }
            $time[2] = 1875 * $time[1];
        }

        $calculated = (($time[0] * pow($time[1], $level - 1) - $time[2]) * ($mainBuildingLevel !== 0 ? pow(0.964, $mainBuildingLevel - 1) : 5) / $this->gameSpeed / ($isWorldWonder ? 2 : 1));

        if ($this->gameSpeed > 500) {
            if ($buildingType === 40) {
                if ($level < 50 && $calculated < 30) {
                    return 30;
                } elseif ($level < 90 && $calculated < 90) {
                    return 60;
                } elseif ($calculated < 120) {
                    return 120;
                }
            }
        }

        if ($this->gameSpeed < 10) {
            return $this->round10($calculated);
        }

        return (int) round($calculated);
    }

    private function calculateUpgradeCosts(int $buildingType, int $level): array
    {
        if ($buildingType === 99) {
            $buildingType = 40;
        }
        if ($buildingType <= 0) {
            return [0, 0, 0, 0];
        }

        $definition = $this->getBuildingDefinition($buildingType);
        $cost = $definition['cost'];
        $k = $definition['k'];

        for ($i = 0; $i < 4; $i++) {
            $cost[$i] = round($cost[$i] * pow($k, $level - 1) / 5) * 5;
            if ($buildingType === 40) {
                if ((($level === 100) && ($i < 3)) || ($cost[$i] > 1e6)) {
                    $cost[$i] = 1000000;
                }
            }
        }

        if ($buildingType <= 4 && $level > 20 && $this->gameSpeed > 10) {
            $rate = 10;
            foreach ($cost as &$resource) {
                $resource *= $rate;
            }
        }

        return $cost;
    }

    private function buildingMaxLevel(int $buildingType, bool $isCapital, bool $real = true): int
    {
        if ($buildingType === 40) {
            return 100;
        }

        if ($buildingType <= 4) {
            if (! $isCapital) {
                return 10;
            }

            return $real ? ($this->allowResourceOverflow ? 1000000000 : 21) : 20;
        }

        $definition = $this->getBuildingDefinition($buildingType);
        $maxLevel = (int) $definition['maxLvl'];

        if (isset($definition['req']['capital']) && (int) $definition['req']['capital'] > 1) {
            if (! $isCapital) {
                $maxLevel = (int) $definition['req']['capital'];
            }
        }

        return $maxLevel;
    }

    private function getBuildingDefinition(int $buildingType): array
    {
        $index = $buildingType - 1;
        if (! isset($this->buildingDefinitions[$index])) {
            throw new InvalidArgumentException('Unknown building type: '.$buildingType);
        }

        return $this->buildingDefinitions[$index];
    }

    private function determineLatestTargetLevel(Village $village, VillageBuilding $building): int
    {
        $latest = $village->buildingUpgrades()
            ->where('slot_number', $building->slot_number)
            ->whereIn('status', [
                VillageBuildingUpgradeStatus::Pending,
                VillageBuildingUpgradeStatus::Processing,
            ])
            ->orderByDesc('target_level')
            ->value('target_level');

        return $latest ? (int) $latest : $building->level;
    }

    private function latestUpgrade(Village $village, VillageBuilding $building): ?VillageBuildingUpgrade
    {
        return $village->buildingUpgrades()
            ->where('slot_number', $building->slot_number)
            ->whereIn('status', [
                VillageBuildingUpgradeStatus::Pending,
                VillageBuildingUpgradeStatus::Processing,
            ])
            ->orderByDesc('completes_at')
            ->first();
    }

    private function nextQueuePosition(Village $village): int
    {
        $position = $village->buildingUpgrades()
            ->max('queue_position');

        return $position === null ? 0 : ((int) $position + 1);
    }

    private function round10(float $value): int
    {
        return (int) (round($value / 10) * 10);
    }
}
