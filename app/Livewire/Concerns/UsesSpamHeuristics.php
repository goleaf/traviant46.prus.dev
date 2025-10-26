<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Services\Communication\SpamHeuristicsService;

trait UsesSpamHeuristics
{
    protected function spamHeuristics(): SpamHeuristicsService
    {
        /** @var SpamHeuristicsService $service */
        $service = app(SpamHeuristicsService::class);

        return $service;
    }
}
