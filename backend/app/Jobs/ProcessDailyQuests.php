<?php

namespace App\Jobs;

use App\Models\Game\DailyQuest;
use App\Models\GameConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessDailyQuests implements ShouldQueue
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
        $chunkSize = $this->chunkSize ?? (int) config('game.daily_quests.reset_chunk_size', 500);
        $configuration = GameConfiguration::current();

        if (!$configuration->shouldResetDailyQuests()) {
            return;
        }

        $processed = 0;
        DailyQuest::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($quests) use (&$processed): void {
                foreach ($quests as $quest) {
                    $this->resetQuest($quest);
                    $processed++;
                }
            });

        $configuration->markDailyQuestsReset();

        Log::info('Daily quests reset completed.', [
            'processed' => $processed,
        ]);
    }

    private function resetQuest(DailyQuest $quest): void
    {
        try {
            DB::transaction(static function () use ($quest): void {
                $lockedQuest = DailyQuest::query()
                    ->whereKey($quest->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedQuest === null) {
                    return;
                }

                $lockedQuest->progress = null;
                $lockedQuest->current_step = 0;
                $lockedQuest->points = 0;
                $lockedQuest->completed_at = null;
                $lockedQuest->reward_claimed_at = null;
                $lockedQuest->save();
            }, 5);
        } catch (Throwable $throwable) {
            Log::error('Failed to reset daily quest progress.', [
                'quest_id' => $quest->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }
}
