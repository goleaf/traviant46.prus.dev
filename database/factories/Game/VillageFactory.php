<?php

declare(strict_types=1);

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
            'legacy_kid' => $this->faker->unique()->numberBetween(1, 1_000_000),
            'user_id' => null,
            'alliance_id' => null,
            'watcher_user_id' => null,
            'name' => $this->faker->unique()->city(),
            'population' => $this->faker->numberBetween(80, 400),
            'loyalty' => 100,
            'culture_points' => $this->faker->numberBetween(0, 1_000),
            'x_coordinate' => $this->faker->numberBetween(-400, 400),
            'y_coordinate' => $this->faker->numberBetween(-400, 400),
            'terrain_type' => $this->faker->numberBetween(1, 13),
            'village_category' => $this->faker->randomElement(['normal', 'capital', 'oasis']),
            'is_capital' => false,
            'is_wonder_village' => false,
            'resource_balances' => [
                'wood' => $this->faker->numberBetween(500, 3_000),
                'clay' => $this->faker->numberBetween(500, 3_000),
                'iron' => $this->faker->numberBetween(500, 3_000),
                'crop' => $this->faker->numberBetween(500, 3_000),
            ],
            'storage' => [
                'warehouse' => 8_000,
                'granary' => 8_000,
                'extra' => ['warehouse' => 0, 'granary' => 0],
            ],
            'production' => [
                'wood' => $this->faker->numberBetween(40, 120),
                'clay' => $this->faker->numberBetween(40, 120),
                'iron' => $this->faker->numberBetween(40, 120),
                'crop' => $this->faker->numberBetween(30, 90),
            ],
            'defense_bonus' => [
                'wall' => $this->faker->numberBetween(0, 200),
                'hero' => $this->faker->numberBetween(0, 50),
            ],
            'founded_at' => $this->faker->dateTimeBetween('-7 days'),
            'abandoned_at' => null,
            'last_loyalty_change_at' => null,
        ];
    }
}
