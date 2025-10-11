<?php

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithUnitMovements;
use App\Models\Game\UnitMovement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAttackArrival implements ShouldQueue
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
            ->missionIn([
                UnitMovement::MISSION_ATTACK,
                UnitMovement::MISSION_RAID,
                UnitMovement::MISSION_SPY,
                UnitMovement::MISSION_ADVENTURE,
            ])
            ->due()
            ->orderBy('arrives_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (UnitMovement $movement): void {
                $this->processMovement($movement, function (UnitMovement $lockedMovement): array {
                    return [
                        'resolution' => [
                            'type' => $lockedMovement->mission,
                            'completed_at' => now()->toIso8601String(),
                        ],
                    ];
                }, 'attack');
            });
    }
}

