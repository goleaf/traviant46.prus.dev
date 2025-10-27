<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use App\Repositories\Game\MovementRepository;
use App\Repositories\Game\TroopRepository;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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
    ) {
    }

    /**
     * Execute the action and persist the troop movement entry.
     *
     * @param array<string, mixed> $payload
     */
    public function execute(
        Village $origin,
        Village $target,
        string $movementType,
        array $payload,
    ): MovementOrder {
        // Build a keyed collection of the origin village units for quick lookups.
        $origin->loadMissing('units');
        $unitInventory = $origin->units->keyBy(
            /** @param VillageUnit $unit */
            static fn (VillageUnit $unit): int => (int) $unit->unit_type_id,
        );

        // Normalise the requested unit payload so we only dispatch troops that actually exist.
        $normalisedUnits = $this->normaliseUnits($unitInventory, (array) ($payload['units'] ?? []));

        if ($normalisedUnits === []) {
            // Fail fast to prevent empty movements from being queued.
            throw new InvalidArgumentException('At least one unit must be dispatched when creating a movement.');
        }

        // Extract timeline markers, falling back to sane defaults when omitted.
        $departAt = $this->resolveDate($payload['depart_at'] ?? null) ?? Carbon::now();
        $arriveAt = $this->resolveDate($payload['arrive_at'] ?? null) ?? $departAt->copy();
        $returnAt = $this->resolveDate($payload['return_at'] ?? null);

        // Split metadata from the movement payload so large preview blobs do not pollute the troop manifest.
        $metadata = (array) ($payload['metadata'] ?? []);
        $movementPayload = $this->preparePayloadSnapshot($payload, $normalisedUnits);

        // Resolve ownership — explicit user id overrides the village owner for sitter launches.
        $userId = $this->resolveUserId($origin, $payload['user_id'] ?? null);

        // Update troop quantities so the origin village reflects the outgoing march immediately.
        foreach ($normalisedUnits as $unitTypeId => $quantity) {
            $unitModel = $unitInventory->get($unitTypeId);

            if ($unitModel instanceof VillageUnit) {
                // Delegate persistence to the repository so future side-effects live in one place.
                $this->troopRepository->reduceUnit($unitModel, $quantity);
            }
        }

        // Persist the movement order record and return it to the caller for UI feedback.
        return $this->movementRepository->createMovement(
            origin: $origin,
            target: $target,
            movementType: $movementType,
            payload: $movementPayload,
            metadata: $metadata,
            departAt: $departAt,
            arriveAt: $arriveAt,
            mission: is_string($payload['mission'] ?? null) ? $payload['mission'] : null,
            userId: $userId,
            returnAt: $returnAt,
        );
    }

    /**
     * @param Collection<int, VillageUnit> $availableUnits
     * @param array<int|string, mixed> $requested
     * @return array<int, int>
     */
    private function normaliseUnits(Collection $availableUnits, array $requested): array
    {
        $normalised = [];

        foreach ($requested as $unitId => $amount) {
            $unitTypeId = (int) $unitId;
            $quantity = max(0, (int) $amount);

            if ($quantity <= 0) {
                continue;
            }

            $unitModel = $availableUnits->get($unitTypeId);

            if (! $unitModel instanceof VillageUnit) {
                continue;
            }

            $available = max(0, (int) $unitModel->quantity);

            if ($available <= 0) {
                continue;
            }

            $normalised[$unitTypeId] = min($quantity, $available);
        }

        ksort($normalised);

        return $normalised;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, int> $normalisedUnits
     * @return array<string, mixed>
     */
    private function preparePayloadSnapshot(array $payload, array $normalisedUnits): array
    {
        // Strip transient keys that are stored in dedicated columns while preserving calculation context.
        $snapshot = Arr::except($payload, ['metadata', 'depart_at', 'arrive_at', 'return_at', 'user_id']);
        $snapshot['units'] = $normalisedUnits;

        return $snapshot;
    }

    private function resolveUserId(Village $origin, mixed $userId): ?int
    {
        if (is_numeric($userId)) {
            return (int) $userId;
        }

        return $origin->user_id !== null ? (int) $origin->user_id : null;
    }

    private function resolveDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return null;
    }
}
