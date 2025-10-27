<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Models\Game\TrainingQueue;
use App\Models\Game\TroopType;
use App\Models\Game\Village;
use App\Repositories\Game\TroopTrainingRepository;
use App\Repositories\Game\VillageRepository;
use App\Support\Travian\TroopCatalog;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Manage troop training requests for a specific village queue.
 */
class TrainTroopsAction
{
    /**
     * Inject repositories responsible for troop metadata and the training queue.
     */
    public function __construct(
        private readonly TroopTrainingRepository $troopTrainingRepository,
        private readonly VillageRepository $villageRepository,
        private readonly TroopCatalog $troopCatalog,
    ) {
    }

    /**
     * Execute the action by validating requirements, deducting resources, and queuing training.
     *
     * @throws InvalidArgumentException
     */
    public function execute(Village $village, TroopType $troopType, int $count): TrainingQueue
    {
        /** @var array<int, array<string, mixed>> $trainingOptions */
        $trainingOptions = $this->troopCatalog->trainingOptions($troopType->code);

        /**
         * Select the first training building option that matches the village state.
         * This ensures we use the highest priority location such as barracks or stable.
         */
        $selectedOption = $this->villageRepository->resolveTrainingOption($village, $trainingOptions);

        if ($selectedOption === null) {
            throw new InvalidArgumentException('No valid training building is available for this troop type.');
        }

        /** @var array<int, array<string, int>> $requirements */
        $requirements = $this->troopCatalog->buildingRequirements($troopType->code);

        if (! $this->villageRepository->meetAllRequirements($village, $requirements)) {
            throw new InvalidArgumentException('The village does not meet the academy requirements for this troop type.');
        }

        if ($count <= 0) {
            throw new InvalidArgumentException('You must train at least one unit.');
        }

        /**
         * Ensure enough resources exist before deducting them in a single atomic update.
         */
        $perUnitCost = Arr::wrap($troopType->train_cost ?? []);
        $totalCost = [];

        foreach (['wood', 'clay', 'iron', 'crop'] as $resource) {
            $resourceCost = (int) ($perUnitCost[$resource] ?? 0);
            $totalCost[$resource] = $resourceCost * $count;
        }

        if (! $this->villageRepository->hasResources($village, $totalCost)) {
            throw new InvalidArgumentException('Village does not have enough resources.');
        }

        /**
         * Calculate the per-unit training duration based on game speed and building level modifiers.
         */
        $baseTime = $this->troopCatalog->baseTime($troopType->code);
        $gameSpeed = (float) config('travian.settings.game.speed', 1);
        $trainingModifier = (float) config('travian.settings.game.extra_training_time_multiplier', 1);
        $speedDivider = max(0.0001, $gameSpeed * $trainingModifier);
        $buildingLevel = (int) ($selectedOption['actual_level'] ?? 0);
        $buildingModifier = $buildingLevel > 1 ? pow(0.9, $buildingLevel - 1) : 1.0;
        $perUnitSeconds = (int) ceil(max(($baseTime / $speedDivider) * $buildingModifier, 1));
        $totalSeconds = $perUnitSeconds * $count;

        /**
         * Determine the queue start time based on existing training batches in the same building.
         */
        $buildingRef = (string) ($selectedOption['ref'] ?? '');
        $startAt = $this->troopTrainingRepository->nextAvailableAt($village, $buildingRef);
        $finishesAt = $startAt->copy()->addSeconds($totalSeconds);

        /**
         * Persist the resource deduction before queueing the training batch to mirror in-game behaviour.
         */
        $updatedVillage = $this->villageRepository->deductResources($village, $totalCost);

        /**
         * Create the training queue entry using the resolved finish timestamp and troop metadata.
         */
        return $this->troopTrainingRepository->queue(
            $updatedVillage,
            $troopType,
            $count,
            $finishesAt,
            $buildingRef,
        );
    }
}
