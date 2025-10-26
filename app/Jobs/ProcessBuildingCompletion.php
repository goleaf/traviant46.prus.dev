<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithShardResolver;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageBuildingUpgrade;
use App\Services\Game\BuildQueueTimingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBuildingCompletion implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(private readonly int $chunkSize = 50, int $shard = 0)
    {
        $this->initializeShardPartitioning($shard);
        $this->onQueue('automation');
    }

    public function handle(): void
    {
        $this->constrainToShard(VillageBuildingUpgrade::query())
            ->due()
            ->orderBy('completes_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (VillageBuildingUpgrade $upgrade): void {
                $this->completeUpgrade($upgrade);
            });
    }

    private function completeUpgrade(VillageBuildingUpgrade $upgrade): void
    {
        try {
            $shouldRecalculateQueue = false;
            $recalculationContext = null;

            DB::transaction(function () use ($upgrade, &$shouldRecalculateQueue, &$recalculationContext): void {
                $lockedUpgrade = VillageBuildingUpgrade::query()
                    ->whereKey($upgrade->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedUpgrade === null) {
                    return;
                }

                if (! $lockedUpgrade->isPending()) {
                    return;
                }

                if ($lockedUpgrade->completes_at?->isFuture()) {
                    return;
                }

                $lockedUpgrade->markProcessing();
                $lockedUpgrade->save();

                $building = $lockedUpgrade->building()
                    ->lockForUpdate()
                    ->first();

                if ($building === null) {
                    $building = new VillageBuilding([
                        'village_id' => $lockedUpgrade->village_id,
                        'slot_number' => $lockedUpgrade->slot_number,
                    ]);
                }

                $building->syncBuildableFromLegacy($lockedUpgrade->building_type);
                $building->level = $lockedUpgrade->target_level;
                $building->village_id = $lockedUpgrade->village_id;
                $building->save();

                if ($lockedUpgrade->village_building_id !== $building->getKey()) {
                    $lockedUpgrade->village_building_id = $building->getKey();
                }

                $lockedUpgrade->markCompleted();
                $lockedUpgrade->save();

                if ((int) $lockedUpgrade->building_type === 15) {
                    $shouldRecalculateQueue = true;
                    $recalculationContext = [
                        'village_id' => (int) $lockedUpgrade->village_id,
                        'main_level' => (int) $building->level,
                    ];
                }
            }, 5);

            if ($shouldRecalculateQueue && is_array($recalculationContext)) {
                $this->recalculateVillageBuildQueue(
                    $recalculationContext['village_id'],
                    $recalculationContext['main_level'],
                );
            }
        } catch (Throwable $throwable) {
            $latestUpgrade = $upgrade->fresh();
            if ($latestUpgrade !== null) {
                $latestUpgrade->markFailed($throwable->getMessage());
                $latestUpgrade->save();
            }

            Log::error('Failed to complete building upgrade.', [
                'upgrade_id' => $upgrade->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    private function recalculateVillageBuildQueue(int $villageId, int $mainBuildingLevel): void
    {
        /** @var Village|null $village */
        $village = Village::query()->find($villageId);

        if ($village === null) {
            return;
        }

        app(BuildQueueTimingService::class)->recalculateForVillage($village, $mainBuildingLevel);
    }
}
