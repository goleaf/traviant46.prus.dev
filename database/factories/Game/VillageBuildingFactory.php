<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Models\Game\VillageBuilding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VillageBuilding>
 */
class VillageBuildingFactory extends Factory
{
    protected $model = VillageBuilding::class;

    public function definition(): array
    {
        return [
            'village_id' => null,
            'slot_number' => $this->faker->numberBetween(1, 40),
            'building_type' => null,
            'level' => 0,
        ];
    }
}
