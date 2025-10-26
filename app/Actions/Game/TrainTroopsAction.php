<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\TroopTrainingRepository;
use App\Repositories\Game\VillageRepository;
use LogicException;

class TrainTroopsAction
{
    public function __construct(
        private readonly VillageRepository $villages,
        private readonly TroopTrainingRepository $troopTraining,
    ) {}

    public function execute(): void
    {
        throw new LogicException('TrainTroopsAction::execute() not implemented.');
    }
}
