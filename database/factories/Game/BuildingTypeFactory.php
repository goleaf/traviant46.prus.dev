<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Models\Game\BuildingType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BuildingType>
 */
class BuildingTypeFactory extends Factory
{
    protected $model = BuildingType::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Main Building',
            'Barracks',
            'Stable',
            'Workshop',
            'Academy',
            'Residence',
            'Palace',
            'Embassy',
            'Marketplace',
            'Granary',
            'Warehouse',
            'City Wall',
        ]);

        return [
            'gid' => fake()->unique()->numberBetween(1, 60),
            'slug' => Str::slug($name.'-'.fake()->unique()->numberBetween(1, 100)),
            'name' => $name,
            'category' => fake()->randomElement(['infrastructure', 'military', 'economy']),
            'max_level' => fake()->numberBetween(10, 20),
            'metadata' => [
                'description' => fake()->sentence(),
            ],
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn () => ['max_level' => null]);
    }
}
