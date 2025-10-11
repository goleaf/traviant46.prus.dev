<?php

namespace App\Jobs;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceBonusUpgrade;
use App\Models\Game\AllianceMember;
use App\Models\GameConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAllianceBonus implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;
    public string $queue = 'automation';

    public function __construct(private readonly ?int $chunkSize = null)
    {
    }

    public function handle(): void
    {
        $configuration = GameConfiguration::current();

        if ($configuration->shouldResetAllianceContributions()) {
            AllianceMember::query()->update(['contribution' => 0]);
            $configuration->markAllianceContributionsReset();

            Log::info('Alliance contributions reset.');
        }

        $chunkSize = $this->chunkSize ?? (int) config('game.alliance.upgrade_chunk_size', 50);

        AllianceBonusUpgrade::query()
            ->due()
            ->orderBy('completes_at')
            ->limit($chunkSize)
            ->get()
            ->each(function (AllianceBonusUpgrade $upgrade): void {
                $this->completeUpgrade($upgrade);
            });
    }

    private function completeUpgrade(AllianceBonusUpgrade $upgrade): void
    {
        try {
            DB::transaction(function () use ($upgrade): void {
                $lockedUpgrade = AllianceBonusUpgrade::query()
                    ->whereKey($upgrade->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedUpgrade === null) {
                    return;
                }

                if ($lockedUpgrade->processed_at !== null) {
                    return;
                }

                if ($lockedUpgrade->completes_at?->isFuture()) {
                    return;
                }

                $alliance = Alliance::query()
                    ->lockForUpdate()
                    ->find($lockedUpgrade->alliance_id);

                if ($alliance !== null) {
                    $alliance->incrementBonusLevel($lockedUpgrade->bonus_type, $lockedUpgrade->target_level);
                }

                $lockedUpgrade->markProcessed();
            }, 5);
        } catch (Throwable $throwable) {
            Log::error('Failed to process alliance bonus upgrade.', [
                'upgrade_id' => $upgrade->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }
}
