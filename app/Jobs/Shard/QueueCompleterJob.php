<?php

declare(strict_types=1);

namespace App\Jobs\Shard;

use App\Enums\Game\BuildQueueState;
use App\Events\Game\BuildCompleted;
use App\Events\Game\TroopsTrained;
use App\Jobs\Concerns\InteractsWithShardResolver;
use App\Models\Game\BuildQueue;
use App\Models\Game\TrainingQueue;
use App\Models\Game\VillageUnit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class QueueCompleterJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        private readonly int $chunkSize = 100,
        int $shard = 0,
    ) {
        $this->initializeShardPartitioning($shard);
        $this->onQueue('automation');
    }

    public function handle(): void
    {
        $this->processBuildQueues();
        $this->processTrainingQueues();
    }

    private function processBuildQueues(): void
    {
        $builder = BuildQueue::query()
            ->where('state', BuildQueueState::Pending)
            ->where('finishes_at', '<=', Carbon::now());

        $builder = $this->constrainToShard($builder);

        $builder
            ->orderBy('finishes_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (BuildQueue $queue): void {
                $this->completeBuild($queue);
            });
    }

    private function processTrainingQueues(): void
    {
        $builder = TrainingQueue::query()
            ->where('finishes_at', '<=', Carbon::now());

        $builder = $this->constrainToShard($builder);

        $builder
            ->orderBy('finishes_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (TrainingQueue $queue): void {
                $this->completeTraining($queue);
            });
    }

    private function completeBuild(BuildQueue $queue): void
    {
        try {
            DB::transaction(function () use ($queue): void {
                $lockedQueue = BuildQueue::query()
                    ->whereKey($queue->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedQueue === null) {
                    return;
                }

                if ($lockedQueue->state !== BuildQueueState::Pending) {
                    return;
                }

                if ($lockedQueue->finishes_at?->isFuture() === true) {
                    return;
                }

                $lockedQueue->markWorking();
                $lockedQueue->save();

                $targetLevel = (int) $lockedQueue->target_level;

                $building = DB::table('buildings')
                    ->where('village_id', $lockedQueue->village_id)
                    ->where('building_type', $lockedQueue->building_type)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($building !== null) {
                    if ((int) ($building->level ?? 0) < $targetLevel) {
                        DB::table('buildings')
                            ->where('id', $building->id)
                            ->update(['level' => $targetLevel]);
                    }
                } else {
                    Log::warning('Build queue completed without an existing building record.', [
                        'queue_id' => $lockedQueue->getKey(),
                        'village_id' => $lockedQueue->village_id,
                        'building_type' => $lockedQueue->building_type,
                    ]);
                }

                $lockedQueue->markDone();
                $lockedQueue->save();

                BuildCompleted::dispatch(
                    $this->villageChannel((int) $lockedQueue->village_id),
                    [
                        'queue_id' => (int) $lockedQueue->getKey(),
                        'village_id' => (int) $lockedQueue->village_id,
                        'building_type' => (int) $lockedQueue->building_type,
                        'target_level' => $targetLevel,
                    ],
                );
            }, 5);
        } catch (Throwable $exception) {
            Log::error('Failed to complete build queue item.', [
                'queue_id' => $queue->getKey(),
                'village_id' => $queue->village_id,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    private function completeTraining(TrainingQueue $queue): void
    {
        try {
            DB::transaction(function () use ($queue): void {
                $lockedQueue = TrainingQueue::query()
                    ->whereKey($queue->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedQueue === null) {
                    return;
                }

                if ($lockedQueue->finishes_at?->isFuture() === true) {
                    return;
                }

                $quantity = (int) $lockedQueue->getAttribute('count');

                $unit = VillageUnit::query()
                    ->where('village_id', $lockedQueue->village_id)
                    ->where('unit_type_id', $lockedQueue->troop_type_id)
                    ->lockForUpdate()
                    ->first();

                if ($unit === null) {
                    $unit = new VillageUnit([
                        'village_id' => $lockedQueue->village_id,
                        'unit_type_id' => $lockedQueue->troop_type_id,
                        'quantity' => 0,
                    ]);
                }

                $unit->quantity = ($unit->quantity ?? 0) + $quantity;
                $unit->save();

                $payload = [
                    'queue_id' => (int) $lockedQueue->getKey(),
                    'village_id' => (int) $lockedQueue->village_id,
                    'unit_type_id' => (int) $lockedQueue->troop_type_id,
                    'quantity' => $quantity,
                    'building_ref' => $lockedQueue->building_ref,
                ];

                $lockedQueue->delete();

                TroopsTrained::dispatch(
                    $this->villageChannel($payload['village_id']),
                    $payload,
                );
            }, 5);
        } catch (Throwable $exception) {
            Log::error('Failed to complete training queue item.', [
                'queue_id' => $queue->getKey(),
                'village_id' => $queue->village_id,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    private function villageChannel(int $villageId): string
    {
        return sprintf('game.villages.%d', $villageId);
    }
}
