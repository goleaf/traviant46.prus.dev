<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Game\UnitTrainingBatchStatus;
use App\Jobs\Concerns\InteractsWithShardResolver;
use App\Models\Game\UnitTrainingBatch;
use App\Models\Game\VillageUnit;
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
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public string $queue = 'automation';

    public function __construct(private readonly int $chunkSize = 50, int $shard = 0)
    {
        $this->initializeShardPartitioning($shard);
    }

    public function handle(): void
    {
        $this->constrainToShard(UnitTrainingBatch::query())
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

                if ($lockedBatch->status !== UnitTrainingBatchStatus::Pending) {
                    return;
                }

                if ($lockedBatch->completes_at?->isFuture()) {
                    return;
                }

                $lockedBatch->markProcessing();
                $lockedBatch->save();

                $unit = VillageUnit::query()
                    ->where('village_id', $lockedBatch->village_id)
                    ->where('unit_type_id', $lockedBatch->unit_type_id)
                    ->lockForUpdate()
                    ->first();

                if ($unit === null) {
                    $unit = new VillageUnit([
                        'village_id' => $lockedBatch->village_id,
                        'unit_type_id' => $lockedBatch->unit_type_id,
                        'quantity' => 0,
                    ]);
                }

                $unit->quantity = ($unit->quantity ?? 0) + $lockedBatch->quantity;
                $unit->save();

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
