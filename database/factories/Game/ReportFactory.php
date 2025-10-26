<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Models\Game\Report;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'world_id' => World::factory(),
            'for_user_id' => User::factory(),
            'kind' => fake()->randomElement(['combat', 'scout', 'trade', 'system']),
            'data' => [
                'casualties' => [
                    'infantry' => fake()->numberBetween(0, 200),
                    'cavalry' => fake()->numberBetween(0, 150),
                ],
                'bounty' => [
                    'wood' => fake()->numberBetween(0, 500),
                    'clay' => fake()->numberBetween(0, 500),
                    'iron' => fake()->numberBetween(0, 500),
                    'crop' => fake()->numberBetween(0, 500),
                ],
                'damages' => [
                    'wall' => fake()->numberBetween(0, 20),
                    'warehouse' => fake()->numberBetween(0, 10),
                ],
            ],
            'created_at' => now(),
        ];
    }

    public function kind(string $kind): self
    {
        return $this->state(fn () => ['kind' => $kind]);
    }
}
