<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AllianceForum;
use App\Models\AllianceTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<AllianceTopic>
 */
class AllianceTopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'forum_id' => AllianceForum::factory(),
            'alliance_id' => function (array $attributes): int {
                $forumId = $attributes['forum_id'] ?? null;

                if ($forumId instanceof AllianceForum) {
                    return $forumId->alliance_id;
                }

                $forum = AllianceForum::find($forumId);

                return (int) ($forum?->alliance_id ?? AllianceForum::factory()->create()->alliance_id);
            },
            'author_id' => User::factory(),
            'acting_sitter_id' => null,
            'title' => fake()->sentence(6),
            'is_locked' => false,
            'is_pinned' => false,
            'last_posted_at' => now(),
        ];
    }
}
