<?php

namespace Database\Factories;

use App\Models\CampaignCustomerSegment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\CampaignCustomerSegment>
 */
class CampaignCustomerSegmentFactory extends Factory
{
    protected $model = CampaignCustomerSegment::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'filters' => [
                [
                    'field' => 'email',
                    'operator' => 'contains',
                    'value' => '@example.com',
                ],
            ],
            'is_active' => true,
            'match_count' => 0,
            'last_calculated_at' => null,
        ];
    }
}
