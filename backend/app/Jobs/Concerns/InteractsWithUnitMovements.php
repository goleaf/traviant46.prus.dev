<?php

namespace App\Jobs\Concerns;

use App\Models\Game\UnitMovement;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

trait InteractsWithUnitMovements
{
    protected function processMovement(UnitMovement $movement, Closure $callback, string $logContext): void
    {
        try {
            DB::transaction(function () use ($movement, $callback): void {
                $lockedMovement = UnitMovement::query()
                    ->whereKey($movement->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedMovement === null) {
                    return;
                }

                if (!$lockedMovement->isDueForProcessing()) {
                    return;
                }

                $lockedMovement->markProcessing();
                $lockedMovement->save();

                $metadata = $callback($lockedMovement);

                if (!is_array($metadata)) {
                    $metadata = [];
                }

                $lockedMovement->markCompleted($metadata);
                $lockedMovement->save();
            }, 5);
        } catch (Throwable $throwable) {
            $this->handleMovementFailure($movement, $throwable, $logContext);

            throw $throwable;
        }
    }

    protected function handleMovementFailure(UnitMovement $movement, Throwable $throwable, string $logContext): void
    {
        $latestMovement = $movement->fresh();
        if ($latestMovement !== null && $latestMovement->status !== UnitMovement::STATUS_COMPLETED) {
            $latestMovement->markFailed($throwable->getMessage());
            $latestMovement->save();
        }

        Log::error(sprintf('Failed to process %s movement.', $logContext), [
            'movement_id' => $movement->getKey(),
            'mission' => $movement->mission,
            'exception' => $throwable,
        ]);
    }
}

