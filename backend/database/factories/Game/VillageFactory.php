<?php

namespace Database\Factories\Game;

use App\Models\Game\Village;
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
            'owner_id' => null,
            'name' => $this->faker->unique()->city(),
            'population' => 0,
            'loyalty' => 100,
            'x_coordinate' => $this->faker->numberBetween(-400, 400),
            'y_coordinate' => $this->faker->numberBetween(-400, 400),
            'is_capital' => false,
            'founded_at' => $this->faker->dateTime(),
        ];
    }
}
