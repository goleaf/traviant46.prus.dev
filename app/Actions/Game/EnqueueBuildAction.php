<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\BuildingQueueRepository;
use App\Repositories\Game\VillageRepository;

/**
 * Prepare building upgrade requests by dispatching them to the queue repository.
 */
class EnqueueBuildAction
{
    /**
     * Inject repositories to coordinate village data and the building queue.
     */
    public function __construct(
        private BuildingQueueRepository $buildingQueueRepository,
        private VillageRepository $villageRepository,
    ) {
    }

    /**
     * Execute the action by adding a building request to the queue.
     */
    public function execute(): void
    {
        // TODO: Provide the concrete implementation for enqueuing building upgrades.
    }
}
