<?php

namespace App\Jobs;

use App\Models\Game\VillageResource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessStorageOverflow implements ShouldQueue
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
            ->whereColumn('current_stock', '>', 'storage_capacity')
            ->chunkById($this->chunkSize, function ($resources): void {
                foreach ($resources as $resource) {
                    $this->trimOverflow($resource);
                }
            });
    }

    private function trimOverflow(VillageResource $resource): void
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

                if ((float) $locked->current_stock <= (float) $locked->storage_capacity) {
                    return;
                }

                $overflow = (float) $locked->current_stock - (float) $locked->storage_capacity;

                $locked->current_stock = (float) $locked->storage_capacity;
                $locked->save();

                Log::info('Trimmed overflowing village resource.', [
                    'resource_id' => $locked->getKey(),
                    'village_id' => $locked->village_id,
                    'resource_type' => $locked->resource_type,
                    'overflow_amount' => round($overflow, 4),
                ]);
            }, 5);
        } catch (Throwable $exception) {
            Log::error('Failed to trim village resource overflow.', [
                'resource_id' => $resource->getKey(),
                'village_id' => $resource->village_id,
                'resource_type' => $resource->resource_type,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
