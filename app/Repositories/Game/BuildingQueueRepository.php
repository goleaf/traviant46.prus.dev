<?php

declare(strict_types=1);

namespace App\Repositories\Game;

use App\Enums\Game\BuildQueueState;
use App\Models\Game\BuildQueue;
use App\Models\Game\Village;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Manage build queue persistence and lookups for a village.
 */
class BuildingQueueRepository
{
    /**
     * Retrieve the number of active build queue slots for the provided village.
     */
    public function activeSlotCount(Village $village): int
    {
        return BuildQueue::query()
            ->where('village_id', $village->getKey())
            ->whereIn('state', [
                BuildQueueState::Pending,
                BuildQueueState::Working,
            ])
            ->count();
    }

    /**
     * Resolve the latest queued level for a specific building type inside the village.
     */
    public function latestQueuedLevel(Village $village, int $buildingType): int
    {
        $latest = BuildQueue::query()
            ->where('village_id', $village->getKey())
            ->where('building_type', $buildingType)
            ->whereIn('state', [
                BuildQueueState::Pending,
                BuildQueueState::Working,
            ])
            ->max('target_level');

        return $latest !== null ? (int) $latest : 0;
    }

    /**
     * Fetch the collection of active queue entries for the village.
     *
     * @return Collection<int, BuildQueue>
     */
    public function activeEntries(Village $village): Collection
    {
        return BuildQueue::query()
            ->where('village_id', $village->getKey())
            ->whereIn('state', [
                BuildQueueState::Pending,
                BuildQueueState::Working,
            ])
            ->orderBy('finishes_at')
            ->get();
    }

    /**
     * Create a build queue entry for the requested village and building.
     */
    public function create(
        Village $village,
        int $buildingType,
        int $targetLevel,
        Carbon $finishesAt
    ): BuildQueue {
        return BuildQueue::query()->create([
            'village_id' => $village->getKey(),
            'building_type' => $buildingType,
            'target_level' => $targetLevel,
            'state' => BuildQueueState::Pending,
            'finishes_at' => $finishesAt,
        ]);
    }
}
