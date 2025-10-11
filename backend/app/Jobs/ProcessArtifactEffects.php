<?php

namespace App\Jobs;

use App\Models\Game\Artifact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessArtifactEffects implements ShouldQueue
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
        $chunkSize = $this->chunkSize ?? (int) config('game.artifacts.processing_chunk_size', 100);

        Artifact::query()
            ->due()
            ->orderBy('next_effect_at')
            ->limit($chunkSize)
            ->get()
            ->each(function (Artifact $artifact): void {
                $this->applyEffect($artifact);
            });
    }

    private function applyEffect(Artifact $artifact): void
    {
        try {
            DB::transaction(static function () use ($artifact): void {
                $lockedArtifact = Artifact::query()
                    ->whereKey($artifact->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedArtifact === null) {
                    return;
                }

                if ($lockedArtifact->next_effect_at?->isFuture()) {
                    return;
                }

                $lockedArtifact->scheduleNextEffect();
            }, 5);
        } catch (Throwable $throwable) {
            Log::error('Failed to process artifact effect.', [
                'artifact_id' => $artifact->getKey(),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }
}
