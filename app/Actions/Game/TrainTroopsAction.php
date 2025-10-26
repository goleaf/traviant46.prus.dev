<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Models\Game\TroopType;
use App\Models\Game\UnitTrainingBatch;
use App\Models\Game\Village;
use App\Models\User;
use App\Services\Game\TroopTrainingService;
use App\Support\Auth\SitterPermissionMatrixResolver;
use InvalidArgumentException;

class TrainTroopsAction
{
    public function __construct(private readonly TroopTrainingService $troopTraining) {}

    /**
     * @param array<string, mixed> $metadata
     */
    public function execute(
        Village $village,
        TroopType $troopType,
        int $quantity,
        ?string $trainingBuilding = null,
        array $metadata = []
    ): UnitTrainingBatch {
        $owner = $this->resolveOwner($village);
        (new SitterPermissionMatrixResolver($owner))->assertAllowed('train');

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be a positive integer.');
        }

        if (! $troopType->exists) {
            throw new InvalidArgumentException('Troop type must exist before training can be scheduled.');
        }

        return $this->troopTraining->train(
            $village,
            (int) $troopType->getKey(),
            $quantity,
            $trainingBuilding,
            $metadata,
        );
    }

    private function resolveOwner(Village $village): User
    {
        $owner = $village->getRelationValue('owner');

        if ($owner instanceof User) {
            return $owner;
        }

        $owner = $village->owner()->first();

        if ($owner instanceof User) {
            $village->setRelation('owner', $owner);

            return $owner;
        }

        throw new InvalidArgumentException('Unable to resolve the owner for the provided village.');
    }
}
