<?php

declare(strict_types=1);

namespace App\Repositories\Game;

use App\Models\Game\TrainingQueue;
use App\Models\Game\TroopType;
use App\Models\Game\Village;
use Illuminate\Support\Carbon;

class TroopTrainingRepository
{
    public function nextAvailableAt(Village $village, string $buildingRef): Carbon
    {
        $latest = TrainingQueue::query()
            ->where('village_id', $village->getKey())
            ->where('building_ref', $buildingRef)
            ->orderByDesc('finishes_at')
            ->value('finishes_at');

        $now = Carbon::now();

        if ($latest === null) {
            return $now;
        }

        $finishesAt = Carbon::parse($latest);

        return $finishesAt->greaterThan($now) ? $finishesAt : $now;
    }

    public function queue(
        Village $village,
        TroopType $troopType,
        int $count,
        Carbon $finishesAt,
        string $buildingRef
    ): TrainingQueue {
        return TrainingQueue::query()->create([
            'village_id' => $village->getKey(),
            'troop_type_id' => $troopType->getKey(),
            'count' => $count,
            'finishes_at' => $finishesAt,
            'building_ref' => $buildingRef,
        ]);
    }
}
