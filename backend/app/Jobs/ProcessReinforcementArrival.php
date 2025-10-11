<?php

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithUnitMovements;
use App\Models\Game\UnitMovement;
use App\Models\Game\VillageUnit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class ProcessReinforcementArrival implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use InteractsWithUnitMovements;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(private readonly int $chunkSize = 100)
    {
    }

    public function viaQueue(): string
    {
        return 'automation';
    }

    public function handle(): void
    {
        UnitMovement::query()
            ->missionIn([UnitMovement::MISSION_REINFORCEMENT])
            ->due()
            ->orderBy('arrives_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (UnitMovement $movement): void {
                $this->processMovement($movement, function (UnitMovement $lockedMovement): array {
                    if ($lockedMovement->target_village_id === null) {
                        throw new RuntimeException('Reinforcement movement requires a target village.');
                    }

                    $units = $lockedMovement->units();

                    foreach ($units as $unit) {
                        $this->incrementVillageUnit($lockedMovement->target_village_id, $unit['unit_type_id'], $unit['quantity']);
                    }

                    return [
                        'reinforcement' => [
                            'units' => $units,
                            'completed_at' => now()->toIso8601String(),
                        ],
                    ];
                }, 'reinforcement');
            });
    }

    private function incrementVillageUnit(int $villageId, int $unitTypeId, int $quantity): void
    {
        $unit = VillageUnit::query()
            ->where('village_id', $villageId)
            ->where('unit_type_id', $unitTypeId)
            ->lockForUpdate()
            ->first();

        if ($unit === null) {
            $unit = new VillageUnit([
                'village_id' => $villageId,
                'unit_type_id' => $unitTypeId,
                'quantity' => 0,
            ]);
        }

        $unit->quantity = ($unit->quantity ?? 0) + max(0, $quantity);
        $unit->save();
    }
}

