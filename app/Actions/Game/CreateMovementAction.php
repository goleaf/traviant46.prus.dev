<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Enums\Game\MovementOrderStatus;
use App\Events\Game\MovementCreated;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\User;
use App\Repositories\Game\MovementRepository;
use App\Repositories\Game\VillageRepository;
use App\Services\Game\MapDistanceService;
use App\Support\Auth\SitterPermissionMatrixResolver;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Throwable;

class CreateMovementAction
{
    public function __construct(
        private readonly VillageRepository $villages,
        private readonly MovementRepository $movements,
        private readonly MapDistanceService $distances,
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(Village $origin, Village $target, string $movementType, array $payload): MovementOrder
    {
        $owner = $this->resolveOwner($origin);
        (new SitterPermissionMatrixResolver($owner))->assertAllowed('send');

        $analysis = $this->distances->profile($origin, $target, (array) ($payload['units'] ?? []));
        $distance = (float) ($analysis['distance'] ?? $this->villages->wrappedDistance($origin, $target));
        $unitSpeed = $this->resolveUnitSpeed($payload, $analysis['slowest_unit_speed'] ?? null);
        $speedFactor = $this->resolveSpeedFactor($payload, $analysis['world_speed_factor'] ?? null);
        $travelSeconds = $this->calculateTravelSeconds($distance, $unitSpeed, $speedFactor);

        $departAt = $this->resolveDepartureTime($payload);
        $arriveAt = $departAt->copy()->addSeconds($travelSeconds);
        $returnAt = $this->resolveReturnTime($payload);

        $metadata = $this->buildMetadata(
            $payload,
            $analysis,
            $unitSpeed,
            $speedFactor,
            $travelSeconds,
        );

        $movementPayload = $this->buildPayload($payload);
        $mission = $this->extractMission($payload);
        $userId = $this->resolveUserId($payload, $origin);

        $movement = $this->movements->createMovement(
            $origin,
            $target,
            $movementType,
            $movementPayload,
            $metadata,
            $departAt,
            $arriveAt,
            $mission,
            $userId,
            $returnAt,
        );

        $this->broadcastMovement($movement, $distance, $travelSeconds);

        return $movement;
    }

    private function calculateTravelSeconds(float $distance, float $unitSpeed, float $speedFactor): int
    {
        $unitSpeed = max($unitSpeed, 0.0001);
        $speedFactor = max($speedFactor, 0.0001);

        return (int) ceil($distance / $unitSpeed / $speedFactor);
    }

    private function resolveUnitSpeed(array $payload, ?float $fallback = null): float
    {
        $candidates = [
            data_get($payload, 'calculation.unit_speed'),
            data_get($payload, 'calculation.slowest_unit_speed'),
            $payload['unit_speed'] ?? null,
            data_get($payload, 'speed.unit'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (float) $candidate > 0) {
                return (float) $candidate;
            }
        }

        if ($fallback !== null && $fallback > 0) {
            return $fallback;
        }

        throw new InvalidArgumentException('Movement payload must include a positive unit speed value.');
    }

    private function resolveSpeedFactor(array $payload, ?float $fallback = null): float
    {
        $candidates = [
            data_get($payload, 'calculation.speed_factor'),
            $payload['speed_factor'] ?? null,
            data_get($payload, 'speed.factor'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (float) $candidate > 0) {
                return (float) $candidate;
            }
        }

        if ($fallback !== null && $fallback > 0) {
            return $fallback;
        }

        $configSpeed = (float) config('travian.settings.game.speed', 1);

        return $configSpeed > 0 ? $configSpeed : 1.0;
    }

    private function resolveDepartureTime(array $payload): Carbon
    {
        $value = $payload['depart_at'] ?? null;

        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value);
            } catch (Throwable) {
                // Fall through to now.
            }
        }

        return Carbon::now();
    }

    private function resolveReturnTime(array $payload): ?Carbon
    {
        $value = $payload['return_at'] ?? null;

        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    private function extractMission(array $payload): ?string
    {
        $mission = $payload['mission'] ?? null;

        if (! is_string($mission)) {
            return null;
        }

        $mission = trim($mission);

        return $mission === '' ? null : $mission;
    }

    private function resolveUserId(array $payload, Village $origin): ?int
    {
        $userId = $payload['user_id'] ?? $origin->user_id;

        if ($userId === null) {
            return null;
        }

        if (is_numeric($userId)) {
            $userId = (int) $userId;

            return $userId > 0 ? $userId : null;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetadata(
        array $payload,
        array $analysis,
        float $unitSpeed,
        float $speedFactor,
        int $travelSeconds
    ): array {
        $metadata = (array) ($payload['metadata'] ?? []);
        $existingCalculation = (array) data_get($metadata, 'calculation', []);
        $distance = (float) ($analysis['distance'] ?? 0.0);
        $unitSpeeds = (array) ($analysis['unit_speeds'] ?? []);
        $slowest = $analysis['slowest_unit_speed'] ?? null;
        $worldSpeed = $analysis['world_speed_factor'] ?? null;
        $wrap = $analysis['wrap_around'] ?? null;

        $metadata['calculation'] = array_merge($existingCalculation, [
            'distance' => $distance,
            'unit_speed' => $unitSpeed,
            'speed_factor' => $speedFactor,
            'travel_seconds' => $travelSeconds,
            'unit_speeds' => $unitSpeeds,
            'slowest_unit_speed' => $slowest,
            'world_speed_factor' => $worldSpeed,
            'wrap_around' => $wrap,
        ]);

        return $metadata;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(array $payload): array
    {
        return Arr::except($payload, ['metadata', 'mission', 'depart_at', 'return_at', 'user_id']);
    }

    private function broadcastMovement(MovementOrder $movement, float $distance, int $travelSeconds): void
    {
        $calculation = (array) data_get($movement->metadata, 'calculation', []);

        $payload = [
            'movement_id' => $movement->getKey(),
            'movement_type' => $movement->movement_type,
            'mission' => $movement->mission,
            'origin_village_id' => (int) $movement->origin_village_id,
            'target_village_id' => (int) $movement->target_village_id,
            'status' => $movement->status instanceof MovementOrderStatus
                ? $movement->status->value
                : (string) $movement->status,
            'depart_at' => $movement->depart_at?->toIso8601String(),
            'arrive_at' => $movement->arrive_at?->toIso8601String(),
            'return_at' => $movement->return_at?->toIso8601String(),
            'distance' => $distance,
            'travel_seconds' => $travelSeconds,
            'unit_speed' => $calculation['unit_speed'] ?? null,
            'speed_factor' => $calculation['speed_factor'] ?? null,
            'world_speed_factor' => $calculation['world_speed_factor'] ?? null,
            'unit_speeds' => $calculation['unit_speeds'] ?? [],
            'payload' => $movement->payload,
            'metadata' => $movement->metadata,
        ];

        $channels = array_unique([
            $this->villages->channelFor($movement->origin_village_id),
            $this->villages->channelFor($movement->target_village_id),
        ]);

        foreach ($channels as $channel) {
            MovementCreated::dispatch($channel, $payload);
        }
    }

    private function resolveOwner(Village $village): User
    {
        $owner = $village->owner ?? $village->getRelationValue('owner');

        if ($owner instanceof User) {
            return $owner;
        }

        $owner = $village->owner()->first();

        if ($owner instanceof User) {
            return $owner;
        }

        throw new InvalidArgumentException('Unable to resolve the owner for the provided village.');
    }
}
