<?php

declare(strict_types=1);

namespace Model;

use App\Models\Artifact;
use App\Models\User;
use App\Models\Village;
use App\Models\WorldSummary;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ArtefactsModel
{
    /**
     * Assign an artefact to the conquering player and alliance while recording capture metadata.
     */
    public function capture(
        Artifact $artifact,
        User $captor,
        ?Village $village = null,
        ?DateTimeInterface $capturedAt = null
    ): Artifact {
        $capturedAtCarbon = $capturedAt ? Carbon::instance($capturedAt) : Carbon::now();

        return DB::transaction(function () use ($artifact, $captor, $village, $capturedAtCarbon): Artifact {
            $metadata = $this->normaliseMetadata($artifact->metadata);
            $history = Arr::get($metadata, 'capture_history', []);
            $history[] = [
                'user_id' => $captor->getKey(),
                'alliance_id' => $captor->alliance_id,
                'village_id' => $village?->getKey(),
                'captured_at' => $capturedAtCarbon->toIso8601String(),
            ];
            $metadata['capture_history'] = array_slice($history, -50);
            $metadata['last_captured_at'] = $capturedAtCarbon->toIso8601String();
            $metadata['captured_by'] = $captor->getKey();
            if ($village !== null) {
                $metadata['holder_village_id'] = $village->getKey();
            }

            $activationDelay = $this->resolveActivationDelaySeconds($metadata);
            $cooldownEndsAt = $activationDelay > 0
                ? $capturedAtCarbon->copy()->addSeconds($activationDelay)
                : $capturedAtCarbon->copy();
            $metadata['cooldown_started_at'] = $capturedAtCarbon->toIso8601String();
            $metadata['cooldown_ends_at'] = $cooldownEndsAt->toIso8601String();

            $artifact->forceFill([
                'owner_user_id' => $captor->getKey(),
                'owner_alliance_id' => $captor->alliance_id,
                'captured_at' => $capturedAtCarbon,
                'cooldown_ends_at' => $cooldownEndsAt,
                'metadata' => $metadata,
            ])->save();

            $this->syncHolder($artifact->holders(), $captor->getKey(), $capturedAtCarbon);
            $this->syncAlliance($artifact->alliances(), $captor->alliance_id, $capturedAtCarbon);

            $this->recordFirstCapture($captor, $capturedAtCarbon);

            return $artifact->refresh();
        });
    }

    /**
     * Activate an artefact once its cooldown has expired.
     */
    public function activate(Artifact $artifact, ?DateTimeInterface $activatedAt = null): Artifact
    {
        if ($artifact->owner_user_id === null) {
            throw new InvalidArgumentException('Cannot activate an unassigned artefact.');
        }

        $activatedAtCarbon = $activatedAt ? Carbon::instance($activatedAt) : Carbon::now();

        if ($artifact->cooldown_ends_at instanceof Carbon && $activatedAtCarbon->lt($artifact->cooldown_ends_at)) {
            throw new InvalidArgumentException('Artefact is still cooling down.');
        }

        return DB::transaction(function () use ($artifact, $activatedAtCarbon): Artifact {
            $metadata = $this->normaliseMetadata($artifact->metadata);
            $metadata['activated_at'] = $activatedAtCarbon->toIso8601String();
            $metadata['is_active'] = true;

            $artifact->forceFill([
                'cooldown_ends_at' => $activatedAtCarbon,
                'metadata' => $metadata,
            ])->save();

            return $artifact->refresh();
        });
    }

    /**
     * Update the artefact's effect payload, supporting incremental patches and removals.
     */
    public function applyEffects(Artifact $artifact, array $effects, bool $merge = true): Artifact
    {
        $prepared = $this->prepareEffects($effects);
        $current = $artifact->effects ?? [];
        $next = $merge ? $this->mergeEffects($current, $prepared) : $prepared;

        if ($current === $next) {
            return $artifact;
        }

        return DB::transaction(function () use ($artifact, $current, $next): Artifact {
            $metadata = $this->normaliseMetadata($artifact->metadata);
            $timestamp = Carbon::now();
            $history = Arr::get($metadata, 'effects_history', []);
            $history[] = [
                'changed_at' => $timestamp->toIso8601String(),
                'previous' => $current,
                'next' => $next,
            ];
            $metadata['effects_history'] = array_slice($history, -25);
            $metadata['last_effect_update_at'] = $timestamp->toIso8601String();

            $artifact->forceFill([
                'effects' => $next,
                'metadata' => $metadata,
            ])->save();

            return $artifact->refresh();
        });
    }

    protected function prepareEffects(array $effects): array
    {
        $prepared = [];
        foreach ($effects as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                throw new InvalidArgumentException('Effect keys must be non-empty strings.');
            }

            if (is_array($value)) {
                $prepared[$key] = $this->prepareEffects($value);
                continue;
            }

            if ($value !== null && !is_scalar($value)) {
                throw new InvalidArgumentException('Effect values must be scalar, array, or null.');
            }

            $prepared[$key] = $value;
        }

        return $prepared;
    }

    protected function mergeEffects(array $current, array $updates): array
    {
        foreach ($updates as $key => $value) {
            if (is_array($value)) {
                $current[$key] = $this->mergeEffects($current[$key] ?? [], $value);
                continue;
            }

            if ($value === null) {
                unset($current[$key]);
                continue;
            }

            $current[$key] = $value;
        }

        return $current;
    }

    protected function normaliseMetadata($metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if ($metadata === null) {
            return [];
        }

        if (is_object($metadata)) {
            return (array) $metadata;
        }

        throw new InvalidArgumentException('Artefact metadata must be an array or null.');
    }

    protected function resolveActivationDelaySeconds(array $metadata): int
    {
        if (array_key_exists('activation_delay_seconds', $metadata)) {
            return max(0, (int) $metadata['activation_delay_seconds']);
        }

        if (array_key_exists('activation_delay_hours', $metadata)) {
            return max(0, (int) $metadata['activation_delay_hours']) * 3600;
        }

        return 0;
    }

    protected function syncHolder(BelongsToMany $relation, int $userId, Carbon $capturedAt): void
    {
        $relation->sync([
            $userId => ['assigned_at' => $capturedAt->toDateTimeString()],
        ]);
    }

    protected function syncAlliance(?BelongsToMany $relation, ?int $allianceId, Carbon $capturedAt): void
    {
        if ($relation === null) {
            return;
        }

        if ($allianceId === null) {
            $relation->sync([]);
            return;
        }

        $relation->sync([
            $allianceId => ['captured_at' => $capturedAt->toDateTimeString()],
        ]);
    }

    protected function recordFirstCapture(User $captor, Carbon $capturedAt): void
    {
        $summary = WorldSummary::query()->latest('id')->first();

        if (!$summary) {
            return;
        }

        if ($summary->first_artifact_player_id !== null) {
            return;
        }

        $summary->forceFill([
            'first_artifact_player_id' => $captor->getKey(),
            'first_artifact_player_name' => $captor->display_name,
            'first_artifact_recorded_at' => $capturedAt,
        ])->save();
    }
}
