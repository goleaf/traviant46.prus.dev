<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alliance>
 */
class AllianceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Alliance '.Str::headline(fake()->unique()->words(2, true)),
            'tag' => strtoupper(Str::substr(Str::replace(' ', '', fake()->unique()->lexify('???')), 0, 4)),
            'description' => fake()->paragraphs(2, true),
            'message_of_day' => fake()->sentence(),
            'founder_id' => User::factory(),
        ];
    }
}
