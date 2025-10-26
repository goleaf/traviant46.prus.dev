<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Repositories\Game\CombatRepository;
use App\Repositories\Game\ReportRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ResolveCombatAction
{
    public function __construct(
        private readonly CombatRepository $combat,
        private readonly ReportRepository $reports,
    ) {}

    /**
     * @param Collection<int, mixed> $movements
     * @return array<string, mixed>
     */
    public function execute(Collection $movements): array
    {
        $movementIds = $movements->map(fn (mixed $movement): int => (int) data_get($movement, 'id'))->all();

        Log::info('Combat resolution queued.', [
            'movement_ids' => $movementIds,
        ]);

        return [
            'status' => 'queued',
            'movement_ids' => $movementIds,
        ];
    }
}
