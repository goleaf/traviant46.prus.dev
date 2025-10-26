<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\TroopRepository;
use App\Repositories\Game\VillageRepository;
use LogicException;

class ApplyStarvationAction
{
    public function __construct(
        private readonly VillageRepository $villages,
        private readonly TroopRepository $troops,
    ) {}

    public function execute(): void
    {
        throw new LogicException('ApplyStarvationAction::execute() not implemented.');
    }
}
