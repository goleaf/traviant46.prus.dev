<?php

namespace App\Jobs;

use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageBuildingUpgrade;
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
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;
    public string $queue = 'automation';

    public function __construct(private readonly int $chunkSize = 50)
    {
    }

    public function handle(): void
    {
        VillageBuildingUpgrade::query()
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
            DB::transaction(function () use ($upgrade): void {
                $lockedUpgrade = VillageBuildingUpgrade::query()
                    ->whereKey($upgrade->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedUpgrade === null) {
                    return;
                }

                if (!$lockedUpgrade->isPending()) {
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

                $building->building_type = $lockedUpgrade->building_type;
                $building->level = $lockedUpgrade->target_level;
                $building->village_id = $lockedUpgrade->village_id;
                $building->save();

                if ($lockedUpgrade->village_building_id !== $building->getKey()) {
                    $lockedUpgrade->village_building_id = $building->getKey();
                }

                $lockedUpgrade->markCompleted();
                $lockedUpgrade->save();
            }, 5);
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
}
