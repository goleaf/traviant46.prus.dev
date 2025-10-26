<?php

declare(strict_types=1);

namespace App\Support\Travian;

use App\Models\Game\TroopType;
use Illuminate\Support\Collection;

/**
 * Provides tribe-aware unit metadata lookups for gameplay calculations.
 */
class UnitCatalog
{
    private const HERO_UPKEEP = 6;

    /**
     * @var array<int, array<int, int>>
     */
    private array $upkeepByTribe = [];

    /**
     * @var array<int, int>
     */
    private array $typeSlotMap = [];

    /**
     * @var array<int, array<int, int>>
     */
    private array $speedByTribe = [];

    /**
     * @return array<int, int>
     */
    public function upkeepMapForTribe(?int $tribe): array
    {
        $tribe = $tribe ?? 0;

        if (! isset($this->upkeepByTribe[$tribe])) {
            $this->upkeepByTribe[$tribe] = $this->loadUpkeepMap($tribe);
        }

        return $this->upkeepByTribe[$tribe];
    }

    public function upkeepForSlot(?int $tribe, int $slot): int
    {
        if ($slot === 11) {
            return self::HERO_UPKEEP;
        }

        return $this->upkeepMapForTribe($tribe)[$slot] ?? 0;
    }

    /**
     * @return array<int, int>
     */
    public function speedMapForTribe(?int $tribe): array
    {
        $tribe = $tribe ?? 0;

        if (! isset($this->speedByTribe[$tribe])) {
            $this->speedByTribe[$tribe] = $this->loadSpeedMap($tribe);
        }

        return $this->speedByTribe[$tribe];
    }

    public function speedForSlot(?int $tribe, int $slot): ?int
    {
        if ($slot <= 0) {
            return null;
        }

        return $this->speedMapForTribe($tribe)[$slot] ?? null;
    }

    /**
     * @param array<string|int, int> $composition
     */
    public function calculateCompositionUpkeep(?int $tribe, array $composition): int
    {
        $total = 0;

        foreach ($composition as $key => $count) {
            $slot = $this->normaliseSlot($key);
            $upkeep = $this->upkeepForSlot($tribe, $slot);
            $total += $upkeep * max(0, (int) $count);
        }

        return $total;
    }

    public function slotForTypeId(?int $typeId): ?int
    {
        if ($typeId === null) {
            return null;
        }

        if (isset($this->typeSlotMap[$typeId])) {
            return $this->typeSlotMap[$typeId];
        }

        $type = TroopType::query()->find($typeId);
        if (! $type instanceof TroopType) {
            return null;
        }

        $this->loadUpkeepMap((int) $type->tribe);

        return $this->typeSlotMap[$typeId] ?? null;
    }

    /**
     * @return array<int, int>
     */
    private function loadUpkeepMap(int $tribe): array
    {
        if ($tribe <= 0) {
            return [];
        }

        /** @var Collection<int, TroopType> $types */
        $types = TroopType::query()
            ->where('tribe', $tribe)
            ->orderBy('id')
            ->get(['id', 'upkeep']);

        $map = [];

        foreach ($types->values() as $index => $type) {
            $slot = $index + 1;
            $map[$slot] = (int) $type->upkeep;
            $this->typeSlotMap[(int) $type->getKey()] = $slot;
        }

        return $map;
    }

    /**
     * @return array<int, int>
     */
    private function loadSpeedMap(int $tribe): array
    {
        if ($tribe <= 0) {
            return [];
        }

        /** @var Collection<int, TroopType> $types */
        $types = TroopType::query()
            ->where('tribe', $tribe)
            ->orderBy('id')
            ->get(['id', 'speed']);

        $map = [];

        foreach ($types->values() as $index => $type) {
            $slot = $index + 1;
            $map[$slot] = (int) $type->speed;
            $this->typeSlotMap[(int) $type->getKey()] = $slot;
        }

        return $map;
    }

    private function normaliseSlot(string|int $key): int
    {
        if (is_int($key)) {
            return $key;
        }

        $trimmed = trim((string) $key);

        if ($trimmed === '') {
            return 0;
        }

        if (str_starts_with($trimmed, 'u')) {
            $trimmed = substr($trimmed, 1);
        }

        if (! is_numeric($trimmed)) {
            return 0;
        }

        return (int) $trimmed;
    }
}
