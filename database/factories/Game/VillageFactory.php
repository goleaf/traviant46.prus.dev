<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @extends Factory<Village>
 */
class VillageFactory extends Factory
{
    protected $model = Village::class;

    /**
     * @var list<string>
     */
    private const STARTER_RESOURCE_FIELDS = [
        'wood', 'wood', 'wood', 'wood',
        'clay', 'clay', 'clay', 'clay',
        'iron', 'iron', 'iron', 'iron',
        'crop', 'crop', 'crop', 'crop', 'crop', 'crop',
    ];

    public function definition(): array
    {
        return [
            'legacy_kid' => $this->faker->unique()->numberBetween(1, 1_000_000),
            'user_id' => null,
            'alliance_id' => null,
            'watcher_user_id' => null,
            'name' => $this->faker->unique()->city(),
            'population' => $this->faker->numberBetween(80, 400),
            'loyalty' => 100,
            'culture_points' => $this->faker->numberBetween(0, 1_000),
            'x_coordinate' => $this->faker->numberBetween(-400, 400),
            'y_coordinate' => $this->faker->numberBetween(-400, 400),
            'terrain_type' => $this->faker->numberBetween(1, 13),
            'village_category' => $this->faker->randomElement(['normal', 'capital', 'oasis']),
            'is_capital' => false,
            'is_wonder_village' => false,
            'resource_balances' => [
                'wood' => $this->faker->numberBetween(500, 3_000),
                'clay' => $this->faker->numberBetween(500, 3_000),
                'iron' => $this->faker->numberBetween(500, 3_000),
                'crop' => $this->faker->numberBetween(500, 3_000),
            ],
            'storage' => [
                'warehouse' => 8_000,
                'granary' => 8_000,
                'extra' => ['warehouse' => 0, 'granary' => 0],
            ],
            'production' => [
                'wood' => $this->faker->numberBetween(40, 120),
                'clay' => $this->faker->numberBetween(40, 120),
                'iron' => $this->faker->numberBetween(40, 120),
                'crop' => $this->faker->numberBetween(30, 90),
            ],
            'defense_bonus' => [
                'wall' => $this->faker->numberBetween(0, 200),
                'hero' => $this->faker->numberBetween(0, 50),
            ],
            'founded_at' => $this->faker->dateTimeBetween('-7 days'),
            'abandoned_at' => null,
            'last_loyalty_change_at' => null,
        ];
    }

    public function starter(): self
    {
        return $this->state(function (): array {
            $now = Carbon::now();

            return [
                'name' => 'New Village',
                'population' => 2,
                'loyalty' => 100,
                'culture_points' => 0,
                'x_coordinate' => 0,
                'y_coordinate' => 0,
                'terrain_type' => 1,
                'village_category' => 'capital',
                'is_capital' => true,
                'resource_balances' => [
                    'wood' => 750,
                    'clay' => 750,
                    'iron' => 750,
                    'crop' => 750,
                ],
                'storage' => [
                    'warehouse' => 800,
                    'granary' => 800,
                    'extra' => ['warehouse' => 0, 'granary' => 0],
                ],
                'production' => [
                    'wood' => 2,
                    'clay' => 2,
                    'iron' => 2,
                    'crop' => 2,
                ],
                'founded_at' => $now,
                'is_wonder_village' => false,
            ];
        })->afterCreating(function (Village $village): void {
            $this->createStarterResourceFields($village);
            $this->createStarterInfrastructure($village);
        });
    }

    private function createStarterResourceFields(Village $village): void
    {
        if (! Schema::hasTable('resource_fields')) {
            return;
        }

        $now = Carbon::now();

        $entries = [];

        foreach (self::STARTER_RESOURCE_FIELDS as $index => $kind) {
            $slot = $index + 1;
            $level = $kind === 'crop' ? 1 : 0;

            $entries[] = [
                'village_id' => $village->getKey(),
                'slot_number' => $slot,
                'kind' => $kind,
                'level' => $level,
                'production_per_hour_cached' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('resource_fields')->upsert(
            $entries,
            ['village_id', 'slot_number'],
            ['kind', 'level', 'production_per_hour_cached', 'updated_at'],
        );
    }

    private function createStarterInfrastructure(Village $village): void
    {
        if (! Schema::hasTable('village_buildings')) {
            return;
        }

        $structures = [
            ['slot' => 1, 'gid' => 1],
            ['slot' => 2, 'gid' => 10],
            ['slot' => 3, 'gid' => 11],
            ['slot' => 4, 'gid' => 16],
        ];

        $now = Carbon::now();

        foreach ($structures as $structure) {
            /** @var BuildingType|null $buildingType */
            $buildingType = BuildingType::query()->where('gid', $structure['gid'])->first();

            DB::table('village_buildings')->updateOrInsert(
                [
                    'village_id' => $village->getKey(),
                    'slot_number' => $structure['slot'],
                ],
                [
                    'building_type' => $structure['gid'],
                    'buildable_type' => $buildingType?->getMorphClass(),
                    'buildable_id' => $buildingType?->getKey(),
                    'level' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}
