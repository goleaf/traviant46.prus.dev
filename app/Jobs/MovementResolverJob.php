<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Game\ResolveCombatAction;
use App\Enums\Game\MovementOrderStatus;
use App\Events\Game\CombatResolved;
use App\Events\Game\TroopsArrived;
use App\Models\Game\MovementOrder;
use App\Jobs\Concerns\InteractsWithShardResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Process due troop movements and emit the corresponding game events.
 */
class MovementResolverJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    /**
     * @param int $chunkSize Number of movement rows to inspect per run.
     * @param int $shard     Allows the scheduler to scope the job to a shard.
     */
    public function __construct(private readonly int $chunkSize = 100, int $shard = 0)
    {
        $this->initializeShardPartitioning($shard);
        $this->onQueue('automation');
    }

    /**
     * Resolve every movement that has reached its arrival time.
     */
    public function handle(ResolveCombatAction $resolveCombat): void
    {
        $this->constrainToShard(MovementOrder::query(), 'target_village_id')
            ->due()
            ->orderBy('arrive_at')
            ->limit($this->chunkSize)
            ->get()
            ->groupBy('target_village_id')
            ->each(function (Collection $movements) use ($resolveCombat): void {
                $this->resolveGroup($movements, $resolveCombat);
            });
    }

    /**
     * Resolve a single target village worth of movements inside a transaction.
     */
    private function resolveGroup(Collection $movements, ResolveCombatAction $resolveCombat): void
    {
        if ($movements->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($movements, $resolveCombat): void {
            $locked = MovementOrder::query()
                ->whereIn('id', $movements->modelKeys())
                ->lockForUpdate()
                ->get()
                ->filter(fn (MovementOrder $movement): bool => $movement->isDueForResolution());

            if ($locked->isEmpty()) {
                return;
            }

            $combatMovements = $locked->filter(fn (MovementOrder $movement): bool => $this->requiresCombat($movement));
            $supportMovements = $locked->reject(fn (MovementOrder $movement): bool => $this->requiresCombat($movement));

            if ($combatMovements->isNotEmpty()) {
                $this->processCombatMovements($combatMovements, $resolveCombat);
            }

            $supportMovements->each(function (MovementOrder $movement): void {
                $this->processNonCombatMovement($movement);
            });
        }, 5);
    }

    /**
     * Execute combat resolution for all attacking movements in the group.
     */
    private function processCombatMovements(Collection $movements, ResolveCombatAction $resolveCombat): void
    {
        $movements->each(function (MovementOrder $movement): void {
            if ($movement->status !== MovementOrderStatus::Processing) {
                $movement->markProcessing();
            }

            $movement->save();
        });

        try {
            $result = $resolveCombat->execute($movements);
        } catch (Throwable $exception) {
            $movements->each(function (MovementOrder $movement) use ($exception): void {
                $movement->markFailed($exception->getMessage());
                $movement->save();
            });

            Log::error('movement.resolve.combat_failed', [
                'target_village_id' => $movements->first()?->target_village_id,
                'movement_ids' => $movements->modelKeys(),
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $movements->each(function (MovementOrder $movement) use ($result): void {
            $movement->markCompleted([
                'type' => 'combat',
                'movement_id' => $movement->getKey(),
                'result' => $result,
            ]);
            $movement->save();

            $this->dispatchTroopsArrived($movement, [
                'resolution' => 'combat',
                'combat_result' => $result,
            ]);
        });

        $this->dispatchCombatResolved($movements, $result);
    }

    /**
     * Apply reinforcement, trade, or returning missions that do not involve combat.
     */
    private function processNonCombatMovement(MovementOrder $movement): void
    {
        if (! $movement->isDueForResolution()) {
            return;
        }

        if ($movement->status !== MovementOrderStatus::Processing) {
            $movement->markProcessing();
        }

        $movement->save();

        try {
            $resolution = match ($movement->movement_type) {
                'reinforcement' => $this->applyReinforcement($movement),
                'trade' => $this->applyTrade($movement),
                'return' => $this->applyReturn($movement),
                default => [],
            };
        } catch (Throwable $exception) {
            $movement->markFailed($exception->getMessage());
            $movement->save();

            Log::error('movement.resolve.support_failed', [
                'movement_id' => $movement->getKey(),
                'movement_type' => $movement->movement_type,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $movement->markCompleted(array_merge(
            ['type' => $movement->movement_type],
            $resolution,
        ));
        $movement->save();

        $this->dispatchTroopsArrived($movement, array_merge(
            ['resolution' => $movement->movement_type],
            $resolution,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function applyReinforcement(MovementOrder $movement): array
    {
        return [
            'units' => $movement->payload['units'] ?? [],
            'mission' => $movement->mission,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyTrade(MovementOrder $movement): array
    {
        return [
            'resources' => $movement->payload['resources'] ?? [],
            'mission' => $movement->mission,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyReturn(MovementOrder $movement): array
    {
        return [
            'units' => $movement->payload['units'] ?? [],
            'origin_village_id' => $movement->origin_village_id,
        ];
    }

    private function dispatchTroopsArrived(MovementOrder $movement, array $payload = []): void
    {
        TroopsArrived::dispatch(
            $this->buildVillageChannel((int) $movement->target_village_id),
            array_merge([
                'movement_id' => $movement->getKey(),
                'movement_type' => $movement->movement_type,
                'origin_village_id' => $movement->origin_village_id,
                'target_village_id' => $movement->target_village_id,
                'status' => $movement->status instanceof MovementOrderStatus
                    ? $movement->status->value
                    : $movement->status,
            ], $payload),
        );
    }

    private function dispatchCombatResolved(Collection $movements, array $result): void
    {
        $targetVillageId = (int) ($movements->first()?->target_village_id ?? 0);

        CombatResolved::dispatch(
            $this->buildVillageChannel($targetVillageId),
            array_merge([
                'movement_ids' => $movements->modelKeys(),
                'target_village_id' => $targetVillageId,
            ], $result),
        );
    }

    /**
     * Determine whether the movement requires combat resolution.
     */
    private function requiresCombat(MovementOrder $movement): bool
    {
        return in_array($movement->movement_type, ['attack', 'raid', 'scout'], true);
    }

    /**
     * Build the broadcast channel name for the target village.
     */
    private function buildVillageChannel(int $villageId): string
    {
        return sprintf('game.village.%d', $villageId);
    }
}
