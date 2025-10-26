<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\World>
 */
class WorldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'World '.fake()->unique()->numberBetween(1, 9999),
            'speed' => fake()->randomFloat(2, 0.5, 5.0),
            'features' => [
                'artifacts' => fake()->boolean(),
                'night_mode' => fake()->boolean(),
                'tribes' => fake()->randomElement(['classic', 'extended']),
            ],
            'starts_at' => fake()->dateTimeBetween('-2 weeks', '+4 weeks'),
            'status' => fake()->randomElement(['scheduled', 'active', 'completed']),
        ];
    }
}
