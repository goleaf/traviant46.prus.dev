<?php

namespace App\Jobs;

use App\Models\Economy\TradeRoute;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessTradeRoutes implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(private readonly int $chunkSize = 50)
    {
        $this->onQueue('automation');
    }

    public function handle(): void
    {
        TradeRoute::query()
            ->due()
            ->orderBy('next_dispatch_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (TradeRoute $route): void {
                $this->processRoute($route);
            });
    }

    private function processRoute(TradeRoute $route): void
    {
        try {
            DB::transaction(function () use ($route): void {
                $lockedRoute = TradeRoute::query()
                    ->whereKey($route->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedRoute === null) {
                    return;
                }

                if (! $lockedRoute->is_active) {
                    return;
                }

                if ($lockedRoute->next_dispatch_at === null || $lockedRoute->next_dispatch_at->isFuture()) {
                    return;
                }

                $origin = Village::query()
                    ->lockForUpdate()
                    ->find($lockedRoute->origin_village_id);

                $target = Village::query()->find($lockedRoute->target_village_id);

                if ($origin === null || $target === null) {
                    $lockedRoute->is_active = false;
                    $lockedRoute->next_dispatch_at = null;
                    $lockedRoute->save();

                    Log::warning('Disabled trade route because one of the villages is missing.', [
                        'trade_route_id' => $lockedRoute->getKey(),
                    ]);

                    return;
                }

                $resources = $this->normaliseResources($lockedRoute->resources ?? []);

                if (array_sum($resources) === 0) {
                    $lockedRoute->scheduleNextDispatch();
                    $lockedRoute->save();

                    return;
                }

                $originStorage = $this->normaliseResources($origin->storage ?? []);
                $dispatch = $this->calculateDispatchResources($resources, $originStorage);

                if (array_sum($dispatch) === 0) {
                    $lockedRoute->scheduleNextDispatch();
                    $lockedRoute->save();

                    return;
                }

                $origin->storage = $this->subtractResources($originStorage, $dispatch);
                $origin->save();

                $travelMinutes = max(1, (int) $lockedRoute->dispatch_interval_minutes);

                $order = new MovementOrder([
                    'user_id' => $lockedRoute->user_id,
                    'origin_village_id' => $origin->getKey(),
                    'target_village_id' => $target->getKey(),
                    'movement_type' => MovementOrder::TYPE_TRADE,
                    'status' => MovementOrder::STATUS_EN_ROUTE,
                    'depart_at' => now(),
                    'arrive_at' => now()->addMinutes($travelMinutes),
                    'payload' => [
                        'resources' => $dispatch,
                        'trade_route_id' => $lockedRoute->getKey(),
                        'travel_minutes' => $travelMinutes,
                        'reference' => Str::uuid()->toString(),
                    ],
                ]);

                $order->save();

                $lockedRoute->scheduleNextDispatch();
                $lockedRoute->save();
            }, 5);
        } catch (Throwable $throwable) {
            Log::error('Failed to process trade route dispatch.', [
                'trade_route_id' => $route->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @return array<string, int>
     */
    private function normaliseResources(array $resources): array
    {
        $defaults = [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ];

        foreach ($defaults as $type => $default) {
            $defaults[$type] = max(0, (int) ($resources[$type] ?? $default));
        }

        return $defaults;
    }

    /**
     * @param array<string, int> $requested
     * @param array<string, int> $available
     * @return array<string, int>
     */
    private function calculateDispatchResources(array $requested, array $available): array
    {
        $dispatch = [];

        foreach ($requested as $type => $amount) {
            $dispatch[$type] = max(0, min($amount, $available[$type] ?? 0));
        }

        return $dispatch;
    }

    /**
     * @param array<string, int> $storage
     * @param array<string, int> $dispatch
     * @return array<string, int>
     */
    private function subtractResources(array $storage, array $dispatch): array
    {
        foreach ($dispatch as $type => $amount) {
            $storage[$type] = max(0, ($storage[$type] ?? 0) - $amount);
        }

        return $storage;
    }
}
