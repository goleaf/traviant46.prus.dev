<?php

namespace Database\Seeders;

use App\Models\BuildingType;
use App\Models\QuestDefinition;
use App\Models\TroopType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class GameplayMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $buildingDefinitions = Config::get('gameplay.buildings', []);
        $troopDefinitions = Config::get('gameplay.troops', []);
        $questDefinitions = Config::get('gameplay.quests', []);

        $buildingIds = [];

        foreach ($buildingDefinitions as $definition) {
            $building = BuildingType::updateOrCreate(
                ['code' => $definition['code']],
                Arr::only($definition, [
                    'name',
                    'category',
                    'max_level',
                    'is_resource_field',
                    'base_cost',
                    'production',
                    'bonuses',
                ])
            );

            $buildingIds[$building->code] = $building->id;
        }

        foreach ($troopDefinitions as $definition) {
            $buildingCode = $definition['training_building'] ?? null;
            $trainingBuildingId = $buildingCode ? ($buildingIds[$buildingCode] ?? null) : null;

            TroopType::updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'tribe' => $definition['tribe'],
                    'training_building_type_id' => $trainingBuildingId,
                    'attack' => $definition['attack'],
                    'defense_infantry' => $definition['defense_infantry'],
                    'defense_cavalry' => $definition['defense_cavalry'],
                    'speed' => $definition['speed'],
                    'carry_capacity' => $definition['carry'],
                    'crop_consumption' => $definition['crop'],
                    'cost' => $definition['cost'],
                ]
            );
        }

        foreach ($questDefinitions as $definition) {
            QuestDefinition::updateOrCreate(
                ['code' => $definition['code']],
                [
                    'category' => $definition['category'],
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'requirements' => $definition['requirements'],
                    'rewards' => $definition['rewards'],
                    'repeatable' => (bool) ($definition['repeatable'] ?? false),
                ]
            );
        }
    }
}
