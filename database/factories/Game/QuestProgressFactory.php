<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Models\Game\Quest;
use App\Models\Game\QuestProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\QuestProgress>
 */
class QuestProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quest_id' => Quest::factory(),
            'state' => QuestProgress::STATE_PENDING,
            'progress' => [
                'objectives' => [],
                'rewards' => [],
            ],
        ];
    }
}
