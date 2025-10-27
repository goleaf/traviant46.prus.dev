<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Support\Travian\StarterVillageBlueprint;
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
        return $this->state(function (array $attributes): array {
            $now = Carbon::now();
            $storageMultiplier = (float) config('travian.settings.game.storage_multiplier', 1);

            return [
                'name' => $attributes['name'] ?? 'New Village',
                'population' => $attributes['population'] ?? 2,
                'loyalty' => $attributes['loyalty'] ?? 100,
                'culture_points' => $attributes['culture_points'] ?? 0,
                'x_coordinate' => $attributes['x_coordinate'] ?? 0,
                'y_coordinate' => $attributes['y_coordinate'] ?? 0,
                'terrain_type' => $attributes['terrain_type'] ?? 1,
                'village_category' => $attributes['village_category'] ?? 'capital',
                'is_capital' => $attributes['is_capital'] ?? true,
                'resource_balances' => $attributes['resource_balances']
                    ?? StarterVillageBlueprint::baseResourceBalances($storageMultiplier),
                'storage' => $attributes['storage']
                    ?? StarterVillageBlueprint::baseStorage($storageMultiplier),
                'production' => $attributes['production']
                    ?? StarterVillageBlueprint::baseProduction(),
                'founded_at' => $attributes['founded_at'] ?? $now,
                'is_wonder_village' => false,
            ];
        })->afterCreating(function (Village $village): void {
            // Seed the starter blueprint structures so every new account meets the Travian spawn contract.
            $this->createStarterResourceFields($village);
            $this->createStarterInfrastructure($village);
        });
    }

    /**
     * Seed starter resource fields while aligning with the unique (village, kind, slot) constraint.
     */
    private function createStarterResourceFields(Village $village): void
    {
        if (! Schema::hasTable('resource_fields')) {
            return;
        }

        $now = Carbon::now();
        $entries = collect(StarterVillageBlueprint::resourceFieldBlueprint())
            ->map(fn (array $field): array => [
                'village_id' => $village->getKey(),
                'slot_number' => $field['slot'],
                'kind' => $field['kind'],
                'level' => $field['level'],
                'production_per_hour_cached' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        // Ensure the starter blueprint always yields the expected 18 resource tiles.
        if (count($entries) !== 18) {
            throw new \RuntimeException('Starter resource blueprint must define exactly 18 fields.');
        }

        DB::table('resource_fields')->upsert(
            $entries,
            ['village_id', 'kind', 'slot_number'],
            ['level', 'production_per_hour_cached', 'updated_at'],
        );
    }

    private function createStarterInfrastructure(Village $village): void
    {
        if (! Schema::hasTable('village_buildings')) {
            return;
        }

        $structures = StarterVillageBlueprint::infrastructure();
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
                    // Persist the target level from the blueprint so the spawn matches the Travian defaults.
                    'level' => $structure['level'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}
