<?php

declare(strict_types=1);

namespace App\Repositories\Game;

use App\Enums\Game\MovementOrderStatus;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class MovementRepository
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     */
    public function createMovement(
        Village $origin,
        Village $target,
        string $movementType,
        array $payload,
        array $metadata,
        DateTimeInterface $departAt,
        DateTimeInterface $arriveAt,
        ?string $mission = null,
        ?int $userId = null,
        ?DateTimeInterface $returnAt = null,
    ): MovementOrder {
        return MovementOrder::query()->create([
            'user_id' => $userId ?? ($origin->user_id !== null ? (int) $origin->user_id : null),
            'origin_village_id' => (int) $origin->getKey(),
            'target_village_id' => (int) $target->getKey(),
            'movement_type' => $movementType,
            'mission' => $mission,
            'status' => MovementOrderStatus::InTransit,
            'depart_at' => Carbon::instance($departAt),
            'arrive_at' => Carbon::instance($arriveAt),
            'return_at' => $returnAt ? Carbon::instance($returnAt) : null,
            'payload' => $payload,
            'metadata' => $metadata,
        ]);
    }
}
