<?php

namespace App\Jobs;

use App\Models\Game\VillageResource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessResourceTick implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(private readonly int $chunkSize = 100)
    {
        $this->onQueue('automation');
    }

    public function handle(): void
    {
        VillageResource::query()
            ->chunkById($this->chunkSize, function ($resources): void {
                $resources
                    ->sortBy(fn (VillageResource $resource) => $resource->last_calculated_at ?? Carbon::createFromTimestamp(0))
                    ->each(function (VillageResource $resource): void {
                        $this->processResource($resource);
                    });
            });
    }

    private function processResource(VillageResource $resource): void
    {
        try {
            DB::transaction(function () use ($resource): void {
                $locked = VillageResource::query()
                    ->whereKey($resource->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($locked === null) {
                    return;
                }

                $now = Carbon::now();
                $lastCalculated = $locked->last_calculated_at ?? $locked->updated_at ?? $now->copy()->subMinute();
                $elapsedSeconds = max(0, $lastCalculated->diffInRealSeconds($now));

                if ($elapsedSeconds === 0) {
                    $locked->markCalculated($now);

                    return;
                }

                $productionPerHour = (float) $locked->production_per_hour;
                $delta = $productionPerHour * ($elapsedSeconds / 3600);

                if (abs($delta) < 0.0001) {
                    $locked->markCalculated($now);

                    return;
                }

                $newStock = (float) $locked->current_stock + $delta;
                $capacity = (float) $locked->storage_capacity;

                if ($locked->resource_type === VillageResource::TYPE_CROP) {
                    $newStock = min($newStock, $capacity);
                } else {
                    $newStock = min($capacity, max(0.0, $newStock));
                }

                $locked->current_stock = round($newStock, 4);
                $locked->last_calculated_at = $now;
                $locked->save();
            }, 5);
        } catch (Throwable $exception) {
            Log::error('Failed to process village resource tick.', [
                'resource_id' => $resource->getKey(),
                'village_id' => $resource->village_id,
                'resource_type' => $resource->resource_type,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
