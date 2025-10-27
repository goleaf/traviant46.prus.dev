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

/**
 * Job responsible for refilling nature garrisons in neutral oases.
 */
class OasisRespawnJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        private readonly int $chunkSize = 50,
        private readonly ?int $fallbackRespawnMinutes = null,
    ) {
        $this->onQueue('automation');
    }

    /**
     * Refill every oasis that has reached its respawn window.
     */
    public function handle(): void
    {
        WorldOasis::query()
            ->dueForRespawn()
            ->with('world')
            ->orderBy('respawn_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (WorldOasis $oasis): void {
                $this->respawnOasis($oasis);
            });
    }

    /**
     * Perform the transactional respawn update for a single oasis.
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

                $nextRespawnAt = $this->nextRespawnTimestamp($lockedOasis);

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
        $preset = $this->presetForType($type);

        if ($preset === null) {
            Log::warning('Missing nature garrison preset for oasis type.', [
                'oasis_type' => $type,
            ]);

            return [];
        }

        $garrison = [];

        foreach (($preset['garrison'] ?? []) as $unit => $count) {
            $garrison[$unit] = (int) $count;
        }

        return $garrison;
    }

    private function nextRespawnTimestamp(WorldOasis $oasis): Carbon
    {
        $minutes = $this->calculateRespawnMinutes($oasis);

        return now()->addMinutes($minutes);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presetForType(int $type): ?array
    {
        $preset = config('oasis.presets.' . $type);

        if (! is_array($preset)) {
            return null;
        }

        return $preset;
    }

    private function calculateRespawnMinutes(WorldOasis $oasis): int
    {
        $preset = $this->presetForType($oasis->type);
        $baseMinutes = (int) ($preset['respawn_minutes'] ?? $this->defaultRespawnMinutes());

        $worldSpeed = $oasis->world?->speed ?? 1.0;

        if ($worldSpeed <= 0) {
            $worldSpeed = 1.0;
        }

        return (int) ceil($baseMinutes / $worldSpeed);
    }

    private function defaultRespawnMinutes(): int
    {
        return $this->fallbackRespawnMinutes ?? (int) config('oasis.default_respawn_minutes', 360);
    }
}
