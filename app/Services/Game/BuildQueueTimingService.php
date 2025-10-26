<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Enums\Game\BuildQueueState;
use App\Models\Game\BuildQueue;
use App\Models\Game\Village;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class BuildQueueTimingService
{
    public function recalculateForVillage(Village $village, int $mainBuildingLevel): void
    {
        $queues = BuildQueue::query()
            ->where('village_id', $village->getKey())
            ->whereIn('state', [BuildQueueState::Pending, BuildQueueState::Working])
            ->get();

        if ($queues->isEmpty()) {
            return;
        }

        $buildingService = new BuildingService(gameSpeed: $this->determineWorldSpeed($village));
        $now = Carbon::now();

        foreach ($queues as $queue) {
            if ($queue->building_type === null || $queue->target_level === null) {
                continue;
            }

            try {
                $newDuration = $buildingService->calculateBuildTime(
                    (int) $queue->building_type,
                    (int) $queue->target_level,
                    $mainBuildingLevel,
                    (bool) $village->is_wonder_village,
                );
            } catch (InvalidArgumentException) {
                continue;
            }

            $startedAt = $queue->created_at ?? $queue->updated_at ?? $now;
            $elapsed = max(0, $now->getTimestamp() - $startedAt->getTimestamp());
            $remaining = max(0, $newDuration - $elapsed);

            $newFinish = $now->copy()->addSeconds($remaining);

            if ($queue->finishes_at === null || $newFinish->lessThan($queue->finishes_at)) {
                $queue->finishes_at = $newFinish;
                $queue->save();
            }
        }
    }

    private function determineWorldSpeed(Village $village): float
    {
        $speed = (float) data_get($village, 'world.speed', 0.0);

        if ($speed <= 0.0) {
            $world = $village->relationLoaded('world')
                ? $village->getRelation('world')
                : $village->world()->first();

            if ($world !== null) {
                $speed = (float) $world->speed;
            }
        }

        if ($speed <= 0.0) {
            $speed = (float) config('travian.settings.game.speed', 1.0);
        }

        return max(0.1, $speed);
    }
}
