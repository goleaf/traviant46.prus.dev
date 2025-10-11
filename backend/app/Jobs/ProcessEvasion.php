<?php

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithUnitMovements;
use App\Models\Game\UnitMovement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use RuntimeException;

class ProcessEvasion implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use InteractsWithUnitMovements;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(private readonly int $chunkSize = 50)
    {
    }

    public function viaQueue(): string
    {
        return 'automation';
    }

    public function handle(): void
    {
        UnitMovement::query()
            ->missionIn([UnitMovement::MISSION_EVASION])
            ->due()
            ->orderBy('arrives_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (UnitMovement $movement): void {
                $this->processMovement($movement, function (UnitMovement $lockedMovement): array {
                    if ($lockedMovement->origin_village_id === null || $lockedMovement->target_village_id === null) {
                        throw new RuntimeException('Evasion movement must define both origin and target villages.');
                    }

                    $units = $lockedMovement->units();

                    if (empty($units)) {
                        return [
                            'evasion' => [
                                'scheduled_return_id' => null,
                                'completed_at' => now()->toIso8601String(),
                            ],
                        ];
                    }

                    $delay = (int) config('gameplay.evasion_return_seconds', 900);
                    $returnArrival = Carbon::now()->addSeconds($delay);

                    $returnMovement = UnitMovement::create([
                        'origin_village_id' => $lockedMovement->target_village_id,
                        'target_village_id' => $lockedMovement->origin_village_id,
                        'mission' => UnitMovement::MISSION_RETURN,
                        'status' => UnitMovement::STATUS_TRAVELLING,
                        'payload' => [
                            'units' => $units,
                            'source_movement_id' => $lockedMovement->getKey(),
                        ],
                        'departed_at' => now(),
                        'arrives_at' => $returnArrival,
                    ]);

                    return [
                        'evasion' => [
                            'scheduled_return_id' => $returnMovement->getKey(),
                            'return_arrives_at' => $returnArrival->toIso8601String(),
                            'return_delay_seconds' => $delay,
                        ],
                    ];
                }, 'evasion');
            });
    }
}

