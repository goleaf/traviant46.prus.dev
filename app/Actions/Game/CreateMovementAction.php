<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Events\Game\MovementCreated;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Repositories\Game\MovementRepository;
use App\Repositories\Game\TroopRepository;
use App\Repositories\Game\VillageRepository;
use App\Services\Game\MapDistanceService;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Orchestrate the creation of troop movements between villages.
 */
class CreateMovementAction
{
    /**
     * Inject repositories for storing movement data and validating troop availability.
     */
    public function __construct(
        private MovementRepository $movementRepository,
        private TroopRepository $troopRepository,
        private MapDistanceService $distances,
        private VillageRepository $villages,
    ) {
    }

    /**
     * Execute the action and persist the troop movement entry.
     *
     * @param array<string, mixed> $payload
     */
    public function execute(Village $origin, Village $target, string $movementType, array $payload): MovementOrder
    {
        // Capture the units we intend to send so the calculator can consider their speeds.
        $units = (array) ($payload['units'] ?? []);

        // Derive core movement metrics (distance, wrap flag, per-unit speeds, and world modifier).
        $profile = $this->distances->profile($origin, $target, $units);

        // Resolve the slowest unit speed so we know how fast the column can travel.
        $unitSpeed = $this->resolveUnitSpeed($payload, $profile);

        // Determine the aggregate speed factor that the world configuration contributes.
        $speedFactor = $this->resolveSpeedFactor($payload, $profile);

        // Calculate the travel duration in seconds while guarding against invalid math.
        $travelSeconds = $profile['distance'] <= 0.0 || $unitSpeed <= 0.0
            ? 0
            : (int) ceil($profile['distance'] / $unitSpeed / $speedFactor);

        // Timestamp bookkeeping for the movement lifecycle.
        $departAt = Carbon::now();
        $arriveAt = $departAt->copy()->addSeconds($travelSeconds);

        // Extract contextual attributes that belong on the movement record rather than inside payload metadata.
        $mission = Arr::get($payload, 'mission');
        $userId = Arr::get($payload, 'user_id');
        $returnAt = Arr::get($payload, 'return_at');

        // Remove meta keys that should not persist inside the payload blob.
        $payloadForStorage = $payload;
        unset($payloadForStorage['metadata'], $payloadForStorage['mission'], $payloadForStorage['user_id'], $payloadForStorage['return_at']);

        // Merge caller supplied metadata with the freshly calculated movement analytics.
        $metadata = $this->mergeMetadata(
            (array) ($payload['metadata'] ?? []),
            [
                'calculation' => [
                    'distance' => $profile['distance'],
                    'travel_seconds' => $travelSeconds,
                    'unit_speed' => $unitSpeed,
                    'speed_factor' => $speedFactor,
                    'world_speed_factor' => $profile['world_speed_factor'],
                    'unit_speeds' => $profile['unit_speeds'],
                    'wrap_around' => $profile['wrap_around'],
                ],
            ],
        );

        // Persist the newly created movement entry through the repository abstraction.
        $movement = $this->movementRepository->createMovement(
            origin: $origin,
            target: $target,
            movementType: $movementType,
            payload: $payloadForStorage,
            metadata: $metadata,
            departAt: $departAt,
            arriveAt: $arriveAt,
            mission: is_string($mission) ? $mission : null,
            userId: is_numeric($userId) ? (int) $userId : null,
            returnAt: $this->normaliseDateTime($returnAt),
        );

        // Broadcast movement notifications to both the origin and target villages.
        $eventPayload = [
            'movement_id' => $movement->getKey(),
            'origin_village_id' => $movement->origin_village_id,
            'target_village_id' => $movement->target_village_id,
            'movement_type' => $movement->movement_type,
            'status' => $movement->status->value,
            'distance' => $profile['distance'],
            'travel_seconds' => $travelSeconds,
            'depart_at' => $departAt->toISOString(),
            'arrive_at' => $arriveAt->toISOString(),
        ];

        event(new MovementCreated($this->villages->channelFor($origin), $eventPayload));
        event(new MovementCreated($this->villages->channelFor($target), $eventPayload));

        // The fully hydrated movement order bubbles back to the caller for further processing.
        return $movement;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $profile
     */
    private function resolveUnitSpeed(array $payload, array $profile): float
    {
        // Candidate sources include direct payload values and calculator insights.
        $candidates = [
            Arr::get($payload, 'unit_speed'),
            Arr::get($payload, 'calculation.unit_speed'),
            Arr::get($profile, 'slowest_unit_speed'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                $value = (float) $candidate;
                if ($value > 0.0) {
                    return $value;
                }
            }
        }

        // Default to a sane baseline to avoid division by zero downstream.
        return 1.0;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $profile
     */
    private function resolveSpeedFactor(array $payload, array $profile): float
    {
        // Allow payload overrides to take precedence when a job pre-computes its timing.
        $candidates = [
            Arr::get($payload, 'speed_factor'),
            Arr::get($payload, 'calculation.speed_factor'),
            Arr::get($profile, 'world_speed_factor'),
        ];

        $base = null;

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                $value = (float) $candidate;
                if ($value > 0.0) {
                    $base = $value;
                    break;
                }
            }
        }

        if ($base === null) {
            $base = 1.0;
        }

        // Fold in global movement modifiers and ensure the factor never drops to zero.
        $movementModifier = (float) (config('travian.settings.game.movement_speed_increase', 1) ?: 1);
        $movementModifier = $movementModifier > 0.0 ? $movementModifier : 1.0;

        $connectionSpeed = (float) (config('travian.connection.speed', 1) ?: 1);
        $connectionSpeed = $connectionSpeed > 0.0 ? $connectionSpeed : 1.0;

        return max(0.1, $base * $movementModifier * $connectionSpeed);
    }

    /**
     * @param array<string, mixed> $original
     * @param array<string, mixed> $additional
     * @return array<string, mixed>
     */
    private function mergeMetadata(array $original, array $additional): array
    {
        // Combine metadata recursively so callers retain previously computed context.
        return array_replace_recursive($original, $additional);
    }

    private function normaliseDateTime(mixed $value): ?\DateTimeInterface
    {
        // Respect DateTimeInterface instances that arrive pre-parsed.
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        // Convert Carbon friendly string inputs while ignoring invalid values.
        if (is_string($value) || is_numeric($value)) {
            try {
                return Carbon::parse((string) $value);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }
}
