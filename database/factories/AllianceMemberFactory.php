<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AllianceRole;
use App\Models\Alliance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AllianceMember>
 */
class AllianceMemberFactory extends Factory
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
            'user_id' => User::factory(),
            'role' => AllianceRole::Member,
            'joined_at' => now(),
        ];
    }
}
