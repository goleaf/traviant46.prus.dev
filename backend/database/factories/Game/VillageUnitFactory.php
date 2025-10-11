<?php

namespace Database\Factories\Game;

use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VillageUnit>
 */
class VillageUnitFactory extends Factory
{
    protected $model = VillageUnit::class;

    public function definition(): array
    {
        return [
            'village_id' => Village::factory(),
            'unit_type_id' => fake()->numberBetween(1, 10),
            'quantity' => fake()->numberBetween(0, 100),
        ];
    }
}

