<?php

namespace Database\Factories\Game;

use App\Models\Game\UnitMovement;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<UnitMovement>
 */
class UnitMovementFactory extends Factory
{
    protected $model = UnitMovement::class;

    public function definition(): array
    {
        $now = Carbon::now();

        return [
            'origin_village_id' => Village::factory(),
            'target_village_id' => Village::factory(),
            'mission' => UnitMovement::MISSION_ATTACK,
            'status' => UnitMovement::STATUS_TRAVELLING,
            'payload' => [
                'units' => [
                    ['unit_type_id' => 1, 'quantity' => fake()->numberBetween(10, 50)],
                ],
            ],
            'metadata' => null,
            'departed_at' => $now->copy()->subMinutes(15),
            'arrives_at' => $now->copy()->subMinutes(1),
            'processing_started_at' => null,
            'processed_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
        ];
    }

    public function mission(string $mission): self
    {
        return $this->state(fn () => ['mission' => $mission]);
    }

    public function withUnits(array $units): self
    {
        return $this->state(fn () => ['payload' => ['units' => $units]]);
    }

    public function fromVillage(Village $village): self
    {
        return $this->state(fn () => ['origin_village_id' => $village->getKey()]);
    }

    public function toVillage(Village $village): self
    {
        return $this->state(fn () => ['target_village_id' => $village->getKey()]);
    }

    public function arrivingAt(Carbon $time): self
    {
        return $this->state(fn () => ['arrives_at' => $time]);
    }
}

