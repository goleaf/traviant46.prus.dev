<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Game\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAdventures implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public string $queue = 'automation';

    public function __construct(private readonly int $chunkSize = 100) {}

    public function handle(): void
    {
        Adventure::query()
            ->due()
            ->orderBy('completes_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (Adventure $adventure): void {
                $this->completeAdventure($adventure);
            });
    }

    private function completeAdventure(Adventure $adventure): void
    {
        try {
            DB::transaction(function () use ($adventure): void {
                $lockedAdventure = Adventure::query()
                    ->whereKey($adventure->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedAdventure === null) {
                    return;
                }

                if ($lockedAdventure->completed_at !== null) {
                    return;
                }

                if ($lockedAdventure->completes_at?->isFuture()) {
                    return;
                }

                $lockedAdventure->markCompleted();
                $lockedAdventure->save();
            }, 5);
        } catch (Throwable $throwable) {
            $latestAdventure = $adventure->fresh();
            if ($latestAdventure !== null) {
                $latestAdventure->markFailed($throwable->getMessage());
                $latestAdventure->save();
            }

            Log::error('Failed to complete adventure.', [
                'adventure_id' => $adventure->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }
}
