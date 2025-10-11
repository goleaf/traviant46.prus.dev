<?php

namespace App\Jobs;

use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMerchantReturn implements ShouldQueue
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
        MovementOrder::query()
            ->dueForArrival()
            ->orderBy('arrive_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (MovementOrder $order): void {
                $this->processOrder($order);
            });
    }

    private function processOrder(MovementOrder $order): void
    {
        try {
            DB::transaction(function () use ($order): void {
                $lockedOrder = MovementOrder::query()
                    ->whereKey($order->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedOrder === null) {
                    return;
                }

                if ($lockedOrder->arrive_at?->isFuture()) {
                    return;
                }

                $payload = $lockedOrder->payload ?? [];

                if ($lockedOrder->movement_type === MovementOrder::TYPE_TRADE) {
                    $this->deliverResources($lockedOrder, $payload);

                    $travelMinutes = max(1, (int) ($payload['travel_minutes'] ?? 0));

                    $payload['delivered_at'] = now();
                    $payload['return_departed_at'] = now();

                    $lockedOrder->movement_type = MovementOrder::TYPE_RETURN;
                    $lockedOrder->status = MovementOrder::STATUS_EN_ROUTE;
                    $lockedOrder->depart_at = now();
                    $lockedOrder->arrive_at = now()->addMinutes($travelMinutes);
                    $lockedOrder->payload = $payload;
                    $lockedOrder->save();

                    return;
                }

                if ($lockedOrder->movement_type === MovementOrder::TYPE_RETURN) {
                    $payload['returned_at'] = now();
                    $lockedOrder->payload = $payload;
                    $lockedOrder->status = MovementOrder::STATUS_COMPLETED;
                    $lockedOrder->save();
                }
            }, 5);
        } catch (Throwable $throwable) {
            Log::error('Failed to process merchant return.', [
                'movement_order_id' => $order->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function deliverResources(MovementOrder $order, array $payload): void
    {
        $resources = $payload['resources'] ?? null;

        if (! is_array($resources)) {
            return;
        }

        $target = Village::query()
            ->lockForUpdate()
            ->find($order->target_village_id);

        if ($target === null) {
            Log::warning('Skipping merchant delivery because the target village is missing.', [
                'movement_order_id' => $order->getKey(),
            ]);

            return;
        }

        $storage = $target->storage ?? [];

        foreach ($resources as $type => $amount) {
            $amount = max(0, (int) $amount);
            $current = max(0, (int) ($storage[$type] ?? 0));
            $storage[$type] = $current + $amount;
        }

        $target->storage = $storage;
        $target->save();
    }
}
