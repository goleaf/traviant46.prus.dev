<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Quest>
 */
class QuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quest_code' => $this->faker->unique()->lexify('quest_????'),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->sentence(12),
            'is_repeatable' => $this->faker->boolean(35),
        ];
    }
}
