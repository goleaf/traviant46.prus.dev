<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\MapRepository;
use App\Repositories\Game\VillageRepository;

/**
 * Handle the creation of a new village within the Travian world.
 */
class CreateVillageAction
{
    /**
     * Inject the repositories required to persist a new village and update the map state.
     */
    public function __construct(
        private VillageRepository $villageRepository,
        private MapRepository $mapRepository,
    ) {
    }

    /**
     * Execute the action and persist the new village.
     */
    public function execute(): void
    {
        // TODO: Provide the concrete implementation for creating a village.
    }
}
