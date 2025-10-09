<?php

namespace App\ValueObjects\Game\Construction;

use Illuminate\Support\Collection;

class BuildingQueueResolution
{
    /**
     * @param Collection<int,BuildingQueueEntry> $upgrades
     * @param Collection<int,BuildingQueueEntry> $masterBuilder
     * @param Collection<int,BuildingQueueEntry> $demolitions
     */
    public function __construct(
        public readonly Collection $upgrades,
        public readonly Collection $masterBuilder,
        public readonly Collection $demolitions,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->upgrades->isEmpty()
            && $this->masterBuilder->isEmpty()
            && $this->demolitions->isEmpty();
    }
}
