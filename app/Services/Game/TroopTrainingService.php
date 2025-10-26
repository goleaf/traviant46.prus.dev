<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Enums\Game\UnitTrainingBatchStatus;
use App\Models\Game\UnitTrainingBatch;
use App\Models\Game\Village;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class TroopTrainingService
{
    private const DEFAULT_PER_UNIT_SECONDS = 60;

    /**
     * Map of normalized training building identifiers to their legacy building type ids.
     *
     * @var array<string, int>
     */
    private const BUILDING_ALIAS_TO_TYPE = [
        'barracks' => 19,
        'great_barracks' => 29,
        'greatbarracks' => 29,
        'great-barracks' => 29,
        'stable' => 20,
        'great_stable' => 30,
        'greatstable' => 30,
        'great-stable' => 30,
        'workshop' => 21,
        'residence' => 25,
        'palace' => 26,
        'command_center' => 44,
        'commandcentre' => 44,
        'command-centre' => 44,
        'commandcenter' => 44,
        'trapper' => 36,
        'hero_mansion' => 37,
        'hero-mansion' => 37,
    ];

    /**
     * Preferred alias for each legacy building type id.
     *
     * @var array<int, string>
     */
    private const BUILDING_TYPE_TO_ALIAS = [
        19 => 'barracks',
        20 => 'stable',
        21 => 'workshop',
        25 => 'residence',
        26 => 'palace',
        29 => 'great_barracks',
        30 => 'great_stable',
        36 => 'trapper',
        37 => 'hero_mansion',
        44 => 'command_center',
    ];

    /**
     * Queue new troops for training.
     *
     * @param array<string, mixed> $metadata
     */
    public function train(
        Village $village,
        int $unitTypeId,
        int $quantity,
        ?string $trainingBuilding = null,
        array $metadata = []
    ): UnitTrainingBatch {
        if (! $this->canTrain($village, $unitTypeId, $quantity, $trainingBuilding)) {
            throw new InvalidArgumentException('The village does not meet the requirements to train the requested troops.');
        }

        $normalisedBuilding = $this->normaliseTrainingBuilding($trainingBuilding);

        [$lastBatch, $nextQueuePosition] = $this->resolveQueueState($village, $normalisedBuilding);

        $now = Carbon::now();
        $startsAt = $lastBatch?->completes_at?->isFuture()
            ? $lastBatch->completes_at->copy()
            : $now->copy();

        $calculation = $this->calculateTrainingTime($village, $unitTypeId, $quantity, $normalisedBuilding);

        $completesAt = $startsAt->copy()->addSeconds($calculation['total_seconds']);

        $batch = new UnitTrainingBatch([
            'village_id' => $village->getKey(),
            'unit_type_id' => $unitTypeId,
            'quantity' => $quantity,
            'queue_position' => $nextQueuePosition,
            'training_building' => $normalisedBuilding,
            'status' => UnitTrainingBatchStatus::Pending,
            'metadata' => array_merge($metadata, ['calculation' => $calculation]),
            'queued_at' => $now,
            'starts_at' => $startsAt,
            'completes_at' => $completesAt,
        ]);

        $batch->save();

        return $batch;
    }

    /**
     * Calculate the training duration for a quantity of troops.
     *
     * @param array<string, mixed> $context
     *
     * @return array{
     *     total_seconds: int,
     *     per_unit_seconds: int,
     *     base_per_unit_seconds: int,
     *     building_level: int,
     *     modifiers: array<string, float>
     * }
     */
    public function calculateTrainingTime(
        Village $village,
        int $unitTypeId,
        int $quantity,
        ?string $trainingBuilding = null,
        array $context = []
    ): array {
        $quantity = max(0, $quantity);

        $basePerUnit = $context['base_per_unit'] ?? $this->resolveBaseTrainingTime($unitTypeId);
        $buildingLevel = $this->resolveBuildingLevel($village, $trainingBuilding);

        $buildingModifier = $this->calculateBuildingModifier($buildingLevel);
        $perUnitSeconds = $basePerUnit * $buildingModifier;

        $percentageReduction = isset($context['percentage_reduction'])
            ? (float) $context['percentage_reduction']
            : 0.0;

        if ($percentageReduction !== 0.0) {
            $perUnitSeconds *= max(0.0, 1 - ($percentageReduction / 100));
        }

        $speedMultiplier = isset($context['speed_multiplier'])
            ? (float) $context['speed_multiplier']
            : 1.0;

        if ($speedMultiplier !== 1.0) {
            $perUnitSeconds *= $speedMultiplier;
        }

        $perUnitSeconds = (int) ceil(max($perUnitSeconds, 1));
        $totalSeconds = $perUnitSeconds * $quantity;

        return [
            'total_seconds' => $totalSeconds,
            'per_unit_seconds' => $perUnitSeconds,
            'base_per_unit_seconds' => (int) $basePerUnit,
            'building_level' => $buildingLevel,
            'modifiers' => [
                'building' => $buildingModifier,
                'percentage_reduction' => $percentageReduction,
                'speed_multiplier' => $speedMultiplier,
            ],
        ];
    }

    public function canTrain(
        Village $village,
        int $unitTypeId,
        int $quantity,
        ?string $trainingBuilding = null
    ): bool {
        if ($quantity <= 0) {
            return false;
        }

        if ($this->resolveBaseTrainingTime($unitTypeId) <= 0) {
            return false;
        }

        $buildingType = $this->resolveBuildingType($trainingBuilding);
        if ($buildingType !== null) {
            $hasBuilding = $village->buildings()
                ->where('building_type', $buildingType)
                ->where('level', '>', 0)
                ->exists();

            if (! $hasBuilding) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{0: UnitTrainingBatch|null, 1: int}
     */
    protected function resolveQueueState(Village $village, ?string $trainingBuilding): array
    {
        $activeStatuses = [
            UnitTrainingBatchStatus::Pending,
            UnitTrainingBatchStatus::Processing,
        ];

        $activeQuery = UnitTrainingBatch::query()
            ->where('village_id', $village->getKey())
            ->whereIn('status', $activeStatuses);

        if ($trainingBuilding !== null) {
            $activeQuery->where('training_building', $trainingBuilding);
        }

        $lastBatch = $activeQuery
            ->orderByDesc('completes_at')
            ->first();

        $maxQueuePosition = UnitTrainingBatch::query()
            ->where('village_id', $village->getKey())
            ->when($trainingBuilding !== null, function ($query) use ($trainingBuilding) {
                $query->where('training_building', $trainingBuilding);
            })
            ->max('queue_position');

        $nextQueuePosition = $maxQueuePosition === null ? 0 : ((int) $maxQueuePosition + 1);

        return [$lastBatch, $nextQueuePosition];
    }

    protected function resolveBaseTrainingTime(int $unitTypeId): int
    {
        $configured = null;

        if (function_exists('config')) {
            $configured = config("game.units.training_time.$unitTypeId");

            if ($configured === null) {
                $configured = config("units.types.$unitTypeId.training_time");
            }

            if ($configured === null) {
                $configured = config('game.units.default_training_time');
            }
        }

        if ($configured !== null) {
            return max(0, (int) $configured);
        }

        return self::DEFAULT_PER_UNIT_SECONDS;
    }

    protected function resolveBuildingLevel(Village $village, ?string $trainingBuilding): int
    {
        $buildingType = $this->resolveBuildingType($trainingBuilding);

        if ($buildingType === null) {
            return 0;
        }

        return (int) ($village->buildings()
            ->where('building_type', $buildingType)
            ->max('level') ?? 0);
    }

    protected function calculateBuildingModifier(int $buildingLevel): float
    {
        if ($buildingLevel <= 1) {
            return 1.0;
        }

        return pow(0.9, $buildingLevel - 1);
    }

    protected function resolveBuildingType(?string $trainingBuilding): ?int
    {
        if ($trainingBuilding === null || $trainingBuilding === '') {
            return null;
        }

        if (is_numeric($trainingBuilding)) {
            return (int) $trainingBuilding;
        }

        $normalised = $this->normaliseKey($trainingBuilding);

        return self::BUILDING_ALIAS_TO_TYPE[$normalised] ?? null;
    }

    protected function normaliseTrainingBuilding(?string $trainingBuilding): ?string
    {
        if ($trainingBuilding === null || $trainingBuilding === '') {
            return null;
        }

        if (is_numeric($trainingBuilding)) {
            $buildingType = (int) $trainingBuilding;

            return self::BUILDING_TYPE_TO_ALIAS[$buildingType] ?? (string) $buildingType;
        }

        $normalised = $this->normaliseKey($trainingBuilding);
        $buildingType = self::BUILDING_ALIAS_TO_TYPE[$normalised] ?? null;

        if ($buildingType !== null) {
            return self::BUILDING_TYPE_TO_ALIAS[$buildingType] ?? $normalised;
        }

        return $normalised;
    }

    protected function normaliseKey(string $value): string
    {
        $value = strtolower($value);
        $value = str_replace([' ', '-', '.'], '_', $value);

        return trim($value, '_');
    }
}
