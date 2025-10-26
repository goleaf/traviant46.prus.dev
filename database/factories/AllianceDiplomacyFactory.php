<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AllianceDiplomacyStatus;
use App\Enums\AllianceDiplomacyType;
use App\Models\Alliance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AllianceDiplomacy>
 */
class AllianceDiplomacyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $alliance = Alliance::factory();

        return [
            'alliance_id' => $alliance,
            'target_alliance_id' => Alliance::factory(),
            'type' => AllianceDiplomacyType::NonAggression,
            'status' => AllianceDiplomacyStatus::Pending,
            'note' => fake()->sentence(),
            'initiated_by' => User::factory(),
            'responded_by' => null,
            'responded_at' => null,
        ];
    }
}
