<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Game\ApplyStarvationAction;
use App\Jobs\Concerns\InteractsWithShardResolver;
use App\Models\Game\Village;
use App\Notifications\Game\VillageStarvationNotification;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class CropStarvationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public string $queue = 'automation';

    /**
     * @param int $shard Allows the scheduler to scope the job to a shard.
     */
    public function __construct(int $shard = 0)
    {
        $this->initializeShardPartitioning($shard);
    }

    public function handle(ApplyStarvationAction $applyStarvation): void
    {
        $now = Carbon::now();

        $this->constrainToShard(Village::query())
            ->where('resource_balances->crop', '<', 0)
            ->chunkById(100, function (Collection $villages) use ($applyStarvation, $now): void {
                $this->processChunk($villages, $applyStarvation, $now);
            });
    }

    private function processChunk(Collection $villages, ApplyStarvationAction $applyStarvation, Carbon $now): void
    {
        /** @var EloquentCollection<int, Village> $villages */
        $villages->loadMissing(['owner', 'watcher']);

        foreach ($villages as $village) {
            if (! $this->granaryEtaPassed($village, $now)) {
                continue;
            }

            try {
                $applyStarvation->execute($village);
            } catch (LogicException $exception) {
                Log::warning('game.starvation.unimplemented', [
                    'village_id' => $village->getKey(),
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            $this->notifyStakeholders($village);
        }
    }

    private function granaryEtaPassed(Village $village, Carbon $now): bool
    {
        $eta = $this->resolveGranaryEta($village);

        return $eta !== null && $eta->lessThanOrEqualTo($now);
    }

    private function resolveGranaryEta(Village $village): ?Carbon
    {
        $storage = (array) ($village->storage ?? []);

        $candidates = [
            $storage['granary_empty_eta'] ?? null,
            $storage['granary_empty_at'] ?? null,
            data_get($storage, 'granary.empty_eta'),
            data_get($storage, 'granary.empty_at'),
            data_get($storage, 'timers.granary_empty'),
        ];

        foreach ($candidates as $value) {
            $timestamp = $this->normaliseEtaValue($value);
            if ($timestamp instanceof Carbon) {
                return $timestamp;
            }
        }

        return null;
    }

    private function normaliseEtaValue(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return null;
            }

            if (Str::isMatch('/^\d+$/', $trimmed)) {
                return Carbon::createFromTimestamp((int) $trimmed);
            }

            try {
                return Carbon::parse($trimmed);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    private function notifyStakeholders(Village $village): void
    {
        $recipients = collect([$village->owner, $village->watcher])
            ->filter(fn ($user) => $user !== null)
            ->uniqueStrict(fn ($user) => $user->getKey());

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new VillageStarvationNotification(
                villageId: (int) $village->getKey(),
                villageName: (string) $village->name,
                coordinates: $village->coordinates,
                cropBalance: (int) data_get($village->resource_balances, 'crop', 0),
            ),
        );
    }
}
