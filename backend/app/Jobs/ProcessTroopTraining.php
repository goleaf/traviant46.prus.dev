<?php

namespace App\Jobs;

use App\Models\Game\Unit;
use App\Models\Game\UnitTrainingBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessTroopTraining implements ShouldQueue
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
        UnitTrainingBatch::query()
            ->due()
            ->orderBy('completes_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (UnitTrainingBatch $batch): void {
                $this->completeBatch($batch);
            });
    }

    private function completeBatch(UnitTrainingBatch $batch): void
    {
        try {
            DB::transaction(function () use ($batch): void {
                $lockedBatch = UnitTrainingBatch::query()
                    ->whereKey($batch->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedBatch === null) {
                    return;
                }

                if ($lockedBatch->status !== UnitTrainingBatch::STATUS_PENDING) {
                    return;
                }

                if ($lockedBatch->completes_at?->isFuture()) {
                    return;
                }

                $lockedBatch->markProcessing();
                $lockedBatch->save();

                $unit = Unit::query()
                    ->where('village_id', $lockedBatch->village_id)
                    ->where('unit_type_id', $lockedBatch->unit_type_id)
                    ->lockForUpdate()
                    ->first();

                if ($unit === null) {
                    $unit = new Unit([
                        'village_id' => $lockedBatch->village_id,
                        'unit_type_id' => $lockedBatch->unit_type_id,
                    ]);
                }

                $unit->train($lockedBatch->quantity);

                $lockedBatch->markCompleted();
                $lockedBatch->save();
            }, 5);
        } catch (Throwable $throwable) {
            $latestBatch = $batch->fresh();
            if ($latestBatch !== null) {
                $latestBatch->markFailed($throwable->getMessage());
                $latestBatch->save();
            }

            Log::error('Failed to process troop training batch.', [
                'batch_id' => $batch->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }
}
