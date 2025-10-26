<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\MovementRepository;
use App\Repositories\Game\TroopRepository;

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
     */
    public function execute(): void
    {
        // TODO: Provide the concrete implementation for creating troop movements.
    }
}
