<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Models\Game\VillageResource;
use App\Models\Game\VillageUnit;
use App\Models\User;
use DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class GameWorldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buildingTypes = $this->seedBuildingTypes();

        $player = User::query()->firstWhere('username', 'playerone')
            ?? User::factory()->create([
                'username' => 'playerone',
                'email' => 'player@example.com',
                'email_verified_at' => Carbon::now(),
            ]);

        $secondary = User::query()
            ->whereKeyNot($player->getKey())
            ->where('role', StaffRole::Player->value)
            ->first()
            ?? User::factory()->create([
                'username' => 'travianfan',
                'email' => 'travianfan@example.com',
                'email_verified_at' => Carbon::now(),
            ]);

        $capital = Village::factory()->create([
            'user_id' => $player->id,
            'name' => $player->username."'s Capital",
            'is_capital' => true,
            'x_coordinate' => 12,
            'y_coordinate' => -8,
            'terrain_type' => 6,
            'village_category' => 'capital',
            'founded_at' => Carbon::now()->subDays(2),
        ]);

        $outpost = Village::factory()->create([
            'user_id' => $player->id,
            'name' => $player->username.' Outpost',
            'is_capital' => false,
            'x_coordinate' => 18,
            'y_coordinate' => -4,
            'terrain_type' => 3,
            'village_category' => 'normal',
        ]);

        $neighbour = Village::factory()->create([
            'user_id' => $secondary->id,
            'name' => $secondary->username.' Village',
            'is_capital' => true,
            'x_coordinate' => 34,
            'y_coordinate' => -2,
            'terrain_type' => 9,
            'village_category' => 'normal',
        ]);

        $this->seedVillageResources($capital, [
            'wood' => ['level' => 9, 'production' => 120],
            'clay' => ['level' => 8, 'production' => 105],
            'iron' => ['level' => 7, 'production' => 96],
            'crop' => ['level' => 6, 'production' => 72],
        ]);
        $this->seedVillageResources($outpost);
        $this->seedVillageResources($neighbour, [
            'wood' => ['level' => 5, 'production' => 65],
            'clay' => ['level' => 5, 'production' => 60],
            'iron' => ['level' => 4, 'production' => 48],
            'crop' => ['level' => 7, 'production' => 80],
        ]);

        $this->seedVillageUnits($capital, [
            1 => 180,
            2 => 90,
            3 => 30,
        ]);
        $this->seedVillageUnits($outpost, [
            1 => 60,
            4 => 40,
        ]);
        $this->seedVillageUnits($neighbour, [
            2 => 75,
            5 => 25,
        ]);

        $this->seedVillageInfrastructure($capital, $buildingTypes);
        $this->seedVillageInfrastructure($outpost, $buildingTypes);
    }

    /**
     * @return array<string, BuildingType>
     */
    private function seedBuildingTypes(): array
    {
        $definitions = [
            ['gid' => 1, 'slug' => 'main-building', 'name' => 'Main Building', 'category' => 'infrastructure', 'max_level' => 20],
            ['gid' => 4, 'slug' => 'barracks', 'name' => 'Barracks', 'category' => 'military', 'max_level' => 20],
            ['gid' => 5, 'slug' => 'sawmill', 'name' => 'Sawmill', 'category' => 'economy', 'max_level' => 5],
            ['gid' => 7, 'slug' => 'iron-foundry', 'name' => 'Iron Foundry', 'category' => 'economy', 'max_level' => 5],
            ['gid' => 8, 'slug' => 'grain-mill', 'name' => 'Grain Mill', 'category' => 'economy', 'max_level' => 5],
            ['gid' => 9, 'slug' => 'bakery', 'name' => 'Bakery', 'category' => 'economy', 'max_level' => 5],
            ['gid' => 10, 'slug' => 'warehouse', 'name' => 'Warehouse', 'category' => 'economy', 'max_level' => 20],
            ['gid' => 11, 'slug' => 'granary', 'name' => 'Granary', 'category' => 'economy', 'max_level' => 20],
            ['gid' => 16, 'slug' => 'rally-point', 'name' => 'Rally Point', 'category' => 'military', 'max_level' => 20],
            ['gid' => 17, 'slug' => 'marketplace', 'name' => 'Marketplace', 'category' => 'economy', 'max_level' => 20],
            ['gid' => 19, 'slug' => 'blacksmith', 'name' => 'Smithy', 'category' => 'military', 'max_level' => 20],
            ['gid' => 22, 'slug' => 'academy', 'name' => 'Academy', 'category' => 'military', 'max_level' => 20],
        ];

        $buildingTypes = [];

        foreach ($definitions as $definition) {
            $buildingTypes[$definition['slug']] = BuildingType::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'gid' => $definition['gid'],
                    'name' => $definition['name'],
                    'category' => $definition['category'],
                    'max_level' => $definition['max_level'],
                    'metadata' => [
                        'description' => $definition['name'].' building seeded for demo worlds.',
                    ],
                ],
            );
        }

        return $buildingTypes;
    }

    /**
     * @param array<string, array{level?: int, production?: int}> $overrides
     */
    private function seedVillageResources(Village $village, array $overrides = []): void
    {
        foreach (['wood', 'clay', 'iron', 'crop'] as $resource) {
            $config = $overrides[$resource] ?? [];

            VillageResource::factory()->for($village)->create([
                'resource_type' => $resource,
                'level' => $config['level'] ?? fake()->numberBetween(4, 8),
                'production_per_hour' => $config['production'] ?? fake()->numberBetween(45, 100),
                'storage_capacity' => fake()->numberBetween(2_400, 6_000),
                'bonuses' => [
                    'oasis' => fake()->boolean(25) ? fake()->numberBetween(5, 25) : 0,
                ],
            ]);
        }
    }

    /**
     * @param array<int, int> $units
     */
    private function seedVillageUnits(Village $village, array $units): void
    {
        foreach ($units as $unitType => $quantity) {
            VillageUnit::factory()->for($village)->create([
                'unit_type_id' => $unitType,
                'quantity' => $quantity,
            ]);
        }
    }

    /**
     * @param array<string, BuildingType> $buildingTypes
     */
    private function seedVillageInfrastructure(Village $village, array $buildingTypes): void
    {
        // Record a simple infrastructure blueprint using the existing building types.
        $structures = [
            ['slot' => 1, 'type' => 'main-building', 'level' => 12],
            ['slot' => 2, 'type' => 'barracks', 'level' => 8],
            ['slot' => 3, 'type' => 'marketplace', 'level' => 6],
            ['slot' => 4, 'type' => 'granary', 'level' => 7],
            ['slot' => 5, 'type' => 'warehouse', 'level' => 9],
        ];

        foreach ($structures as $structure) {
            $buildingType = $buildingTypes[$structure['type']] ?? null;

            if ($buildingType === null) {
                continue;
            }

            // The legacy schema stores village buildings in the village_buildings table.
            // Using the query builder keeps compatibility with the current model setup.
            DB::table('village_buildings')->updateOrInsert(
                [
                    'village_id' => $village->getKey(),
                    'slot_number' => $structure['slot'],
                ],
                [
                    'building_type' => $buildingType->gid ?? $buildingType->getKey(),
                    'buildable_type' => $buildingType->getMorphClass(),
                    'buildable_id' => $buildingType->getKey(),
                    'level' => $structure['level'],
                    'updated_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                ],
            );
        }
    }
}
