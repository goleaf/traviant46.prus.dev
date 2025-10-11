<?php

namespace App\Jobs;

use App\Models\Game\Wonder;
use App\Models\GameConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckGameFinish implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;
    public string $queue = 'automation';

    public function handle(): void
    {
        $configuration = GameConfiguration::current();

        if ($configuration->world_finished_at !== null) {
            return;
        }

        $completionLevel = $configuration->wonder_completion_level ?: (int) config('game.wonder.completion_level', 100);

        Wonder::query()
            ->orderByDesc('level')
            ->orderBy('updated_at')
            ->limit(5)
            ->get()
            ->each(function (Wonder $wonder) use ($completionLevel, $configuration): void {
                $this->finalizeWonder($wonder, $completionLevel, $configuration);
            });
    }

    private function finalizeWonder(Wonder $wonder, int $completionLevel, GameConfiguration $configuration): void
    {
        if ($wonder->level < $completionLevel) {
            return;
        }

        try {
            DB::transaction(function () use ($wonder, $completionLevel, $configuration): void {
                $lockedWonder = Wonder::query()
                    ->whereKey($wonder->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedWonder === null) {
                    return;
                }

                if ($lockedWonder->completed_at !== null) {
                    return;
                }

                if ($lockedWonder->level < $completionLevel) {
                    return;
                }

                $lockedWonder->completed_at = now();
                $lockedWonder->save();

                $configuration->markWorldFinished(null, $lockedWonder->owner_id, now());

                Log::info('Wonder of the World completed.', [
                    'wonder_id' => $lockedWonder->getKey(),
                    'owner_id' => $lockedWonder->owner_id,
                ]);
            }, 5);
        } catch (Throwable $throwable) {
            Log::error('Failed to finalize Wonder of the World.', [
                'wonder_id' => $wonder->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }
}
