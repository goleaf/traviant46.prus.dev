<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Alliance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AllianceForum>
 */
class AllianceForumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'alliance_id' => Alliance::factory(),
            'name' => 'Forum '.Str::headline(fake()->unique()->words(2, true)),
            'description' => fake()->paragraph(),
            'position' => fake()->numberBetween(0, 10),
            'visible_to_sitters' => true,
            'moderators_only' => false,
        ];
    }
}
