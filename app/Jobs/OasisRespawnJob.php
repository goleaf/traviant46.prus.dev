<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Game\WorldOasis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OasisRespawnJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const DEFAULT_RESPAWN_INTERVAL_MINUTES = 360;

    /**
     * @var array<int, array<string, int>>
     */
    private const NATURE_GARRISON_PRESETS = [
        1 => [
            'rat' => 12,
            'spider' => 8,
            'wild_boar' => 4,
        ],
        2 => [
            'rat' => 10,
            'snake' => 6,
            'wolf' => 4,
        ],
        3 => [
            'rat' => 8,
            'wolf' => 6,
            'bear' => 3,
        ],
        4 => [
            'rat' => 16,
            'crocodile' => 5,
            'tiger' => 2,
        ],
    ];

    public int $tries = 1;

    public int $timeout = 120;

    public string $queue = 'automation';

    public function __construct(
        private readonly int $chunkSize = 50,
        private readonly int $respawnIntervalMinutes = self::DEFAULT_RESPAWN_INTERVAL_MINUTES,
    ) {}

    public function handle(): void
    {
        WorldOasis::query()
            ->dueForRespawn()
            ->orderBy('respawn_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (WorldOasis $oasis): void {
                $this->respawnOasis($oasis);
            });
    }

    private function respawnOasis(WorldOasis $oasis): void
    {
        try {
            DB::transaction(function () use ($oasis): void {
                $lockedOasis = WorldOasis::query()
                    ->whereKey($oasis->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedOasis === null) {
                    return;
                }

                if ($lockedOasis->respawn_at === null) {
                    return;
                }

                if ($lockedOasis->respawn_at->isFuture()) {
                    return;
                }

                $garrison = $this->determineNatureGarrison($lockedOasis->type);

                if ($garrison === []) {
                    return;
                }

                $nextRespawnAt = $this->nextRespawnTimestamp();

                $lockedOasis->assignNatureGarrison($garrison, $nextRespawnAt);
                $lockedOasis->save();
            }, 5);
        } catch (Throwable $throwable) {
            Log::error('Failed to respawn oasis.', [
                'oasis_id' => $oasis->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @return array<string, int>
     */
    private function determineNatureGarrison(int $type): array
    {
        $preset = self::NATURE_GARRISON_PRESETS[$type] ?? [];

        if ($preset === []) {
            Log::warning('Missing nature garrison preset for oasis type.', [
                'oasis_type' => $type,
            ]);
        }

        foreach ($preset as $unit => $count) {
            $preset[$unit] = (int) $count;
        }

        return $preset;
    }

    private function nextRespawnTimestamp(): Carbon
    {
        return now()->addMinutes($this->respawnIntervalMinutes);
    }
}

