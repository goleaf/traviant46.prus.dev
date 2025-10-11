<?php

namespace Database\Factories\Game;

use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Village>
 */
class VillageFactory extends Factory
{
    protected $model = Village::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => sprintf('%s Village', fake()->unique()->city()),
            'population' => 0,
            'loyalty' => 100,
            'x_coordinate' => fake()->unique()->numberBetween(-400, 400),
            'y_coordinate' => fake()->unique()->numberBetween(-400, 400),
            'is_capital' => false,
            'founded_at' => now(),
        ];
    }
}

