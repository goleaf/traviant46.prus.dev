<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AlliancePost;
use App\Models\AllianceTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<AlliancePost>
 */
class AlliancePostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => AllianceTopic::factory(),
            'alliance_id' => function (array $attributes): int {
                $topicId = $attributes['topic_id'] ?? null;

                if ($topicId instanceof AllianceTopic) {
                    return $topicId->alliance_id;
                }

                $topic = AllianceTopic::find($topicId);

                return (int) ($topic?->alliance_id ?? AllianceTopic::factory()->create()->alliance_id);
            },
            'author_id' => User::factory(),
            'acting_sitter_id' => null,
            'body' => fake()->paragraphs(2, true),
            'edited_by' => null,
            'edited_at' => null,
        ];
    }
}
