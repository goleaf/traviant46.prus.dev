<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Game\WorldOasis;
use App\Support\Game\OasisPresetRepository;
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

    public const DEFAULT_RESPAWN_INTERVAL_MINUTES = 360;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        private readonly int $chunkSize = 50,
    ) {
        $this->onQueue('automation');
    }

    /**
     * Process a batch of due oases and refresh their neutral garrisons.
     */
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

    /**
     * Refresh a single oasis inside a transaction to avoid double spawns.
     */
    private function respawnOasis(WorldOasis $oasis): void
    {
        try {
            DB::transaction(function () use ($oasis): void {
                $lockedOasis = WorldOasis::query()
                    ->whereKey($oasis->getKey())
                    ->with('world')
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

                $nextRespawnAt = $this->calculateNextRespawnTimestamp($lockedOasis);

                if ($nextRespawnAt === null) {
                    return;
                }

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
        $preset = OasisPresetRepository::garrisonForType($type);

        if ($preset === []) {
            Log::warning('Missing nature garrison preset for oasis type.', [
                'oasis_type' => $type,
            ]);
        }

        return $preset;
    }

    /**
     * Determine the next respawn timestamp taking the oasis type and
     * world speed into account so stronger oases return more slowly.
     */
    private function calculateNextRespawnTimestamp(WorldOasis $oasis): ?Carbon
    {
        $respawnMinutes = OasisPresetRepository::respawnMinutesForType($oasis->type);

        if ($respawnMinutes === null) {
            return null;
        }

        $worldSpeed = $oasis->world?->speed ?? 1.0;
        $speed = $worldSpeed > 0 ? $worldSpeed : 1.0;
        $adjustedMinutes = (int) ceil($respawnMinutes / $speed);

        return now()->addMinutes($adjustedMinutes);
    }
}
