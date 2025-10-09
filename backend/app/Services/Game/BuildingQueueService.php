<?php

namespace App\Services\Game;

use App\ValueObjects\Game\Construction\BuildingQueueEntry;
use App\ValueObjects\Game\Construction\BuildingQueueResolution;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BuildingQueueService
{
    /**
     * @param iterable<int,BuildingQueueEntry|array> $buildingQueue
     * @param iterable<int,BuildingQueueEntry|array> $demolitionQueue
     */
    public function resolveQueues(iterable $buildingQueue, iterable $demolitionQueue, CarbonImmutable $now): BuildingQueueResolution
    {
        $buildings = collect($buildingQueue)->map(fn ($entry) => $this->normaliseEntry($entry));
        $demolitions = collect($demolitionQueue)->map(fn ($entry) => $this->normaliseEntry($entry));

        return new BuildingQueueResolution(
            upgrades: $buildings->filter(fn (BuildingQueueEntry $entry) => !$entry->isMasterBuilder && $entry->isDue($now))->values(),
            masterBuilder: $buildings->filter(fn (BuildingQueueEntry $entry) => $entry->isMasterBuilder && $entry->isDue($now))->values(),
            demolitions: $demolitions->filter(fn (BuildingQueueEntry $entry) => $entry->isDue($now))->values(),
        );
    }

    public function dispatch(BuildingQueueResolution $resolution, callable $upgrade, callable $demolition, ?callable $masterBuilder = null): void
    {
        $resolution->upgrades->each(fn (BuildingQueueEntry $entry) => $upgrade($entry));
        $resolution->demolitions->each(fn (BuildingQueueEntry $entry) => $demolition($entry));

        if ($masterBuilder) {
            $resolution->masterBuilder->each(fn (BuildingQueueEntry $entry) => $masterBuilder($entry));
        }
    }

    /**
     * @param BuildingQueueEntry|array $entry
     */
    private function normaliseEntry(mixed $entry): BuildingQueueEntry
    {
        if ($entry instanceof BuildingQueueEntry) {
            return $entry;
        }

        if (!is_array($entry)) {
            throw new InvalidArgumentException('Queue entries must be arrays or BuildingQueueEntry instances.');
        }

        $commence = $entry['commence'] ?? $entry['end_time'] ?? null;
        if ($commence === null) {
            throw new InvalidArgumentException('Queue entries must define a commence timestamp.');
        }

        return new BuildingQueueEntry(
            villageId: (int) ($entry['kid'] ?? $entry['village_id'] ?? 0),
            slot: (int) ($entry['building_field'] ?? $entry['slot'] ?? 0),
            commenceAt: CarbonImmutable::createFromTimestamp((int) $commence),
            isMasterBuilder: (bool) ($entry['isMaster'] ?? $entry['is_master'] ?? false),
            payload: $entry,
        );
    }
}
