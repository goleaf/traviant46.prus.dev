<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Game\ResolveCombatAction;
use App\Enums\Game\MovementOrderStatus;
use App\Events\Game\CombatResolved;
use App\Events\Game\TroopsArrived;
use App\Models\Game\MovementOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MovementResolverJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public string $queue = 'automation';

    public function __construct(private readonly int $chunkSize = 100) {}

    public function handle(ResolveCombatAction $resolveCombat): void
    {
        MovementOrder::query()
            ->due()
            ->orderBy('arrive_at')
            ->limit($this->chunkSize)
            ->get()
            ->groupBy('target_village_id')
            ->each(function (Collection $movements) use ($resolveCombat): void {
                $this->resolveGroup($movements, $resolveCombat);
            });
    }

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

    private function requiresCombat(MovementOrder $movement): bool
    {
        return in_array($movement->movement_type, ['attack', 'raid', 'scout'], true);
    }

    private function buildVillageChannel(int $villageId): string
    {
        return sprintf('game.village.%d', $villageId);
    }
}
