<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Models\Game\TroopType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TroopType>
 */
class TroopTypeFactory extends Factory
{
    protected $model = TroopType::class;

    public function definition(): array
    {
        return [
            'tribe' => fake()->randomElement([1, 2, 3, 6, 7]),
            'code' => 'unit-'.fake()->unique()->lexify('?????'),
            'name' => Str::headline(fake()->words(2, true)),
            'attack' => fake()->numberBetween(5, 150),
            'def_inf' => fake()->numberBetween(5, 150),
            'def_cav' => fake()->numberBetween(5, 150),
            'speed' => fake()->numberBetween(3, 20),
            'carry' => fake()->numberBetween(0, 3000),
            'train_cost' => [
                'wood' => fake()->numberBetween(60, 950),
                'clay' => fake()->numberBetween(50, 900),
                'iron' => fake()->numberBetween(40, 850),
                'crop' => fake()->numberBetween(20, 300),
            ],
            'upkeep' => fake()->numberBetween(1, 6),
        ];
    }
}
