<?php

namespace App\Services\Game;

use App\ValueObjects\Game\Resources\ResourceTickResult;
use App\ValueObjects\Game\Resources\VillageProduction;
use Carbon\CarbonImmutable;

class ResourceTickService
{
    public function tick(VillageProduction $snapshot, CarbonImmutable $now, bool $starvationEnabled = true): ResourceTickResult
    {
        $seconds = max(0, $snapshot->lastUpdatedAt->diffInSeconds($now));

        [$wood, $woodDelta] = $this->advanceResource($snapshot->wood, $snapshot->woodPerHour, $snapshot->maxStore, $seconds);
        [$clay, $clayDelta] = $this->advanceResource($snapshot->clay, $snapshot->clayPerHour, $snapshot->maxStore, $seconds);
        [$iron, $ironDelta] = $this->advanceResource($snapshot->iron, $snapshot->ironPerHour, $snapshot->maxStore, $seconds);

        $netCropPerHour = $snapshot->cropPerHour - $snapshot->upkeep - $snapshot->population;
        [$crop, $cropDelta] = $this->advanceCrop(
            $snapshot->crop,
            $netCropPerHour,
            $snapshot->maxCrop,
            $seconds,
            $snapshot->ownerId,
            $starvationEnabled
        );

        $current = new VillageProduction(
            villageId: $snapshot->villageId,
            ownerId: $snapshot->ownerId,
            wood: $wood,
            clay: $clay,
            iron: $iron,
            crop: $crop,
            woodPerHour: $snapshot->woodPerHour,
            clayPerHour: $snapshot->clayPerHour,
            ironPerHour: $snapshot->ironPerHour,
            cropPerHour: $snapshot->cropPerHour,
            upkeep: $snapshot->upkeep,
            population: $snapshot->population,
            maxStore: $snapshot->maxStore,
            maxCrop: $snapshot->maxCrop,
            lastUpdatedAt: $now,
        );

        return new ResourceTickResult(
            previous: $snapshot,
            current: $current,
            delta: [
                'wood' => $woodDelta,
                'clay' => $clayDelta,
                'iron' => $ironDelta,
                'crop' => $cropDelta,
            ],
        );
    }

    private function advanceResource(float $current, float $perHour, int $capacity, int $seconds): array
    {
        $gain = ($perHour / 3600) * $seconds;
        $next = $current + $gain;
        if ($next > $capacity) {
            $next = $capacity;
        }
        if ($next < 0) {
            $next = 0;
        }

        return [round($next, 4), round($next - $current, 4)];
    }

    private function advanceCrop(float $current, float $netPerHour, int $capacity, int $seconds, int $ownerId, bool $starvationEnabled): array
    {
        $gain = ($netPerHour / 3600) * $seconds;
        $next = $current + $gain;

        if ($ownerId === 1 && $next < 0) {
            $target = $capacity * 2 / 3;
            return [round($target, 4), round($target - $current, 4)];
        }

        if (!$starvationEnabled && $next <= 0) {
            $target = 1000;
            return [round($target, 4), round($target - $current, 4)];
        }

        if ($next > $capacity) {
            $next = $capacity;
        }
        if ($next < 0) {
            $next = 0;
        }

        return [round($next, 4), round($next - $current, 4)];
    }
}
