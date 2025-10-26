<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\CombatRepository;
use App\Repositories\Game\ReportRepository;
use LogicException;

class ResolveCombatAction
{
    public function __construct(
        private readonly CombatRepository $combat,
        private readonly ReportRepository $reports,
    ) {}

    public function execute(): void
    {
        throw new LogicException('ResolveCombatAction::execute() not implemented.');
    }
}
