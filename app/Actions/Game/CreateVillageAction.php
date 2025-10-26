<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\MapRepository;
use App\Repositories\Game\VillageRepository;
use LogicException;

class CreateVillageAction
{
    public function __construct(
        private readonly VillageRepository $villages,
        private readonly MapRepository $maps,
    ) {}

    public function execute(): void
    {
        throw new LogicException('CreateVillageAction::execute() not implemented.');
    }
}
