<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\BuildingQueueRepository;
use App\Repositories\Game\VillageRepository;
use LogicException;

class EnqueueBuildAction
{
    public function __construct(
        private readonly VillageRepository $villages,
        private readonly BuildingQueueRepository $buildingQueues,
    ) {}

    public function execute(): void
    {
        throw new LogicException('EnqueueBuildAction::execute() not implemented.');
    }
}
