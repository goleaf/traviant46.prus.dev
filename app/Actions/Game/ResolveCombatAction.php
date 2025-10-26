<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\CombatRepository;
use App\Repositories\Game\ReportRepository;

/**
 * Resolve combat encounters and persist their resulting reports.
 */
class ResolveCombatAction
{
    /**
     * Inject repositories that encapsulate combat resolution and report storage.
     */
    public function __construct(
        private CombatRepository $combatRepository,
        private ReportRepository $reportRepository,
    ) {
    }

    /**
     * Execute the combat resolution process and store the results.
     */
    public function execute(): void
    {
        // TODO: Provide the concrete implementation for resolving combats.
    }
}
