<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Models\Game\Village;
use App\Models\Game\VillageResource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<VillageResource>
 */
class VillageResourceFactory extends Factory
{
    protected $model = VillageResource::class;

    public function definition(): array
    {
        $resourceType = Arr::random(['wood', 'clay', 'iron', 'crop']);

        return [
            'village_id' => Village::factory(),
            'resource_type' => $resourceType,
            'level' => fake()->numberBetween(1, 10),
            'production_per_hour' => fake()->numberBetween(30, 180),
            'storage_capacity' => fake()->numberBetween(800, 2400),
            'bonuses' => [
                'oasis' => fake()->boolean() ? fake()->numberBetween(5, 25) : 0,
            ],
        ];
    }
}
