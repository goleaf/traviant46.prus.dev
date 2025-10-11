<?php

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithUnitMovements;
use App\Models\Game\UnitMovement;
use App\Models\Game\Village;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class ProcessSettlerArrival implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use InteractsWithUnitMovements;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(private readonly int $chunkSize = 25)
    {
    }

    public function viaQueue(): string
    {
        return 'automation';
    }

    public function handle(): void
    {
        UnitMovement::query()
            ->missionIn([UnitMovement::MISSION_SETTLERS])
            ->due()
            ->orderBy('arrives_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (UnitMovement $movement): void {
                $this->processMovement($movement, function (UnitMovement $lockedMovement): array {
                    $settlement = data_get($lockedMovement->payload, 'settlement');

                    if (!is_array($settlement)) {
                        throw new RuntimeException('Settlement payload is missing.');
                    }

                    $ownerId = data_get($settlement, 'owner_id');
                    $name = data_get($settlement, 'name', 'New Village');
                    $x = data_get($settlement, 'coordinates.x');
                    $y = data_get($settlement, 'coordinates.y');

                    if ($ownerId === null || $x === null || $y === null) {
                        throw new RuntimeException('Settlement payload is incomplete.');
                    }

                    $existingVillage = Village::query()
                        ->where('x_coordinate', $x)
                        ->where('y_coordinate', $y)
                        ->lockForUpdate()
                        ->first();

                    if ($existingVillage !== null) {
                        throw new RuntimeException('Target coordinates are already occupied.');
                    }

                    $village = new Village([
                        'owner_id' => $ownerId,
                        'name' => $name,
                        'population' => (int) data_get($settlement, 'population', 0),
                        'loyalty' => (int) data_get($settlement, 'loyalty', 100),
                        'x_coordinate' => (int) $x,
                        'y_coordinate' => (int) $y,
                        'is_capital' => (bool) data_get($settlement, 'is_capital', false),
                        'founded_at' => now(),
                    ]);

                    $village->save();

                    return [
                        'settlement' => [
                            'village_id' => $village->getKey(),
                            'completed_at' => now()->toIso8601String(),
                        ],
                    ];
                }, 'settlers');
            });
    }
}

