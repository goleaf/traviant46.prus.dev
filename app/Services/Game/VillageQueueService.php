<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Enums\Game\VillageBuildingUpgradeStatus;
use App\Models\Game\Village;
use App\Models\Game\VillageBuildingUpgrade;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

/**
 * Queue aggregator for the modern infrastructure screen (legacy OnLoadBuildingsDorfCtrl path).
 *
 * Responsibilities migrated from the legacy dispatcher:
 * - expose the full building queue with deterministic ordering and timing metadata.
 * - provide convenience flags for the active upgrade and remaining construction
 *   duration so the Livewire UI can render progress bars without additional SQL.
 * - normalise the segment payload produced by the BuildingService for multi-level
 *   upgrades.
 */
class VillageQueueService
{
    /**
     * Build a queue summary for the provided village.
     *
     * @return array{
     *     village_id: int,
     *     entries: array<int, array{
     *         id: int,
     *         slot: int,
     *         building_type: int|null,
     *         building_name: string,
     *         current_level: int,
     *         target_level: int,
     *         status: string,
     *         queue_position: int,
     *         is_active: bool,
     *         starts_at: ?string,
     *         completes_at: ?string,
     *         remaining_seconds: int,
     *         segments: array<int, array<string, int>>
     *     }>,
     *     active_entry: array<string, mixed>|null,
     *     next_completion_at: ?string
     * }
     */
    public function summarize(Village $village): array
    {
        $now = Carbon::now();

        $village->loadMissing([
            'buildingUpgrades' => static function (Relation $relation): void {
                $relation->getQuery()
                    ->orderBy('queue_position')
                    ->orderBy('id');
            },
            'buildingUpgrades.buildingType',
        ]);

        $entries = $village->buildingUpgrades
            ->map(fn (VillageBuildingUpgrade $upgrade, int $index) => $this->mapUpgrade($upgrade, $index, $now))
            ->values()
            ->all();

        $activeEntry = collect($entries)->firstWhere('is_active', true);
        $nextCompletionAt = collect($entries)
            ->pluck('completes_at')
            ->filter()
            ->sort()
            ->first();

        return [
            'village_id' => (int) $village->getKey(),
            'entries' => $entries,
            'active_entry' => $activeEntry ?: null,
            'next_completion_at' => $nextCompletionAt,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     slot: int,
     *     building_type: int|null,
     *     building_name: string,
     *     current_level: int,
     *     target_level: int,
     *     status: string,
     *     queue_position: int,
     *     is_active: bool,
     *     starts_at: ?string,
     *     completes_at: ?string,
     *     remaining_seconds: int,
     *     segments: array<int, array<string, int>>
     * }
     */
    private function mapUpgrade(VillageBuildingUpgrade $upgrade, int $index, Carbon $now): array
    {
        $queuePosition = $upgrade->queue_position ?? ($index + 1);
        $isActive = $upgrade->status === VillageBuildingUpgradeStatus::Processing
            || ($upgrade->status === VillageBuildingUpgradeStatus::Pending && $queuePosition === 1);

        $startsAt = $upgrade->starts_at?->toIso8601String();
        $completesAt = $upgrade->completes_at?->toIso8601String();

        $remainingSeconds = 0;
        if ($upgrade->completes_at !== null) {
            $remainingSeconds = max(0, (int) $now->diffInSeconds($upgrade->completes_at, false));
        }

        return [
            'id' => (int) $upgrade->getKey(),
            'slot' => (int) $upgrade->slot_number,
            'building_type' => $upgrade->building_type !== null ? (int) $upgrade->building_type : null,
            'building_name' => $upgrade->buildingType?->name
                ?? __('Building :gid', ['gid' => $upgrade->building_type ?? '?']),
            'current_level' => (int) $upgrade->current_level,
            'target_level' => (int) $upgrade->target_level,
            'status' => $upgrade->status instanceof VillageBuildingUpgradeStatus
                ? $upgrade->status->value
                : (string) $upgrade->status,
            'queue_position' => (int) $queuePosition,
            'is_active' => $isActive,
            'starts_at' => $startsAt,
            'completes_at' => $completesAt,
            'remaining_seconds' => $remainingSeconds,
            'segments' => $this->normaliseSegments($upgrade),
        ];
    }

    /**
     * @return array<int, array<string, int>>
     */
    private function normaliseSegments(VillageBuildingUpgrade $upgrade): array
    {
        $segments = $upgrade->metadata['segments'] ?? [];

        if (! is_array($segments)) {
            return [];
        }

        return collect($segments)
            ->filter(static fn ($segment) => is_array($segment))
            ->map(static function (array $segment): array {
                $level = isset($segment['level']) && is_numeric($segment['level'])
                    ? (int) $segment['level']
                    : 0;
                $duration = isset($segment['duration']) && is_numeric($segment['duration'])
                    ? (int) $segment['duration']
                    : 0;

                return [
                    'level' => $level,
                    'duration' => $duration,
                ];
            })
            ->values()
            ->all();
    }
}
