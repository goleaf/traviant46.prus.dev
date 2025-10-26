<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Enums\Game\UnitTrainingBatchStatus;
use App\Models\Game\TroopType;
use App\Models\Game\UnitTrainingBatch;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<UnitTrainingBatch>
 */
class UnitTrainingBatchFactory extends Factory
{
    protected $model = UnitTrainingBatch::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 50);
        $queuedAt = Carbon::now();
        $startsAt = (clone $queuedAt)->addMinutes(fake()->numberBetween(0, 3));
        $perUnitSeconds = fake()->numberBetween(30, 120);
        $totalSeconds = $perUnitSeconds * $quantity;
        $completesAt = (clone $startsAt)->addSeconds($totalSeconds);

        return [
            'village_id' => Village::factory(),
            'unit_type_id' => TroopType::factory(),
            'quantity' => $quantity,
            'queue_position' => fake()->numberBetween(0, 4),
            'training_building' => fake()->randomElement(['barracks', 'stable', 'workshop']),
            'status' => UnitTrainingBatchStatus::Pending,
            'metadata' => [
                'calculation' => [
                    'per_unit_seconds' => $perUnitSeconds,
                    'total_seconds' => $totalSeconds,
                ],
            ],
            'queued_at' => $queuedAt,
            'starts_at' => $startsAt,
            'completes_at' => $completesAt,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UnitTrainingBatchStatus::Processing,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UnitTrainingBatchStatus::Completed,
            'processed_at' => Carbon::now(),
        ]);
    }

    public function failed(string $reason = 'failed'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UnitTrainingBatchStatus::Failed,
            'failed_at' => Carbon::now(),
            'failure_reason' => $reason,
        ]);
    }
}
