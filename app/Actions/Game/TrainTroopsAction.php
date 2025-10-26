<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\TroopRepository;
use App\Repositories\Game\TroopTrainingRepository;

/**
 * Manage troop training requests for a specific village queue.
 */
class TrainTroopsAction
{
    /**
     * Inject repositories responsible for troop metadata and the training queue.
     */
    public function __construct(
        private TroopTrainingRepository $troopTrainingRepository,
        private TroopRepository $troopRepository,
    ) {
    }

    /**
     * Execute the action and dispatch the troop training job.
     */
    public function execute(): void
    {
        // TODO: Provide the concrete implementation for training troops.
    }
}
