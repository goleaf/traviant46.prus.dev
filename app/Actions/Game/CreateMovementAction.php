<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\MovementRepository;
use App\Repositories\Game\VillageRepository;
use LogicException;

class CreateMovementAction
{
    public function __construct(
        private readonly VillageRepository $villages,
        private readonly MovementRepository $movements,
    ) {}

    public function execute(): void
    {
        throw new LogicException('CreateMovementAction::execute() not implemented.');
    }
}
