<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\TroopRepository;
use App\Repositories\Game\VillageRepository;

/**
 * Apply starvation effects to villages when upkeep exceeds production.
 */
class ApplyStarvationAction
{
    /**
     * Inject repositories to access troop counts and village resource production.
     */
    public function __construct(
        private TroopRepository $troopRepository,
        private VillageRepository $villageRepository,
    ) {
    }

    /**
     * Execute the starvation balancing adjustments.
     */
    public function execute(): void
    {
        // TODO: Provide the concrete implementation for applying starvation effects.
    }
}
