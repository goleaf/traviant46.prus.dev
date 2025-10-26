<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game\BuildingCatalog;
use App\Models\Game\BuildingType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class BuildingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalogDefinitions = $this->definitions();
        if ($catalogDefinitions->isEmpty()) {
            return;
        }

        $buildingTypes = BuildingType::query()
            ->whereIn('slug', $catalogDefinitions->keys()->all())
            ->get()
            ->keyBy('slug');

        $catalogDefinitions->each(function (array $payload, string $slug) use ($buildingTypes): void {
            $buildingType = $buildingTypes->get($slug);

            BuildingCatalog::query()->updateOrCreate(
                ['building_slug' => $slug],
                [
                    'building_type_id' => $buildingType?->getKey(),
                    'name' => $payload['name'],
                    'prerequisites' => $payload['prerequisites'],
                    'bonuses_per_level' => $payload['bonuses_per_level'] ?? null,
                    'storage_capacity_per_level' => $payload['storage_capacity_per_level'] ?? null,
                ],
            );
        });
    }

    /**
     * @return Collection<string, array{
     *     name: string,
     *     prerequisites: array<int, array<string, mixed>>,
     *     bonuses_per_level?: array<int, float|int>,
     *     storage_capacity_per_level?: array<int, int>
     * }>
     */
    private function definitions(): Collection
    {
        $productionBonuses = $this->productionBonusLevels(5);
        $storageCapacities = $this->storageCapacityLevels(20);

        return collect([
            'sawmill' => [
                'name' => 'Sawmill',
                'prerequisites' => [
                    ['type' => 'resource-field', 'slug' => 'woodcutter', 'name' => 'Woodcutter', 'level' => 10],
                    ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 5],
                ],
                'bonuses_per_level' => $productionBonuses,
            ],
            'grain-mill' => [
                'name' => 'Grain Mill',
                'prerequisites' => [
                    ['type' => 'resource-field', 'slug' => 'cropland', 'name' => 'Cropland', 'level' => 5],
                    ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 5],
                ],
                'bonuses_per_level' => $productionBonuses,
            ],
            'iron-foundry' => [
                'name' => 'Iron Foundry',
                'prerequisites' => [
                    ['type' => 'resource-field', 'slug' => 'iron-mine', 'name' => 'Iron Mine', 'level' => 10],
                    ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 5],
                ],
                'bonuses_per_level' => $productionBonuses,
            ],
            'bakery' => [
                'name' => 'Bakery',
                'prerequisites' => [
                    ['type' => 'resource-field', 'slug' => 'cropland', 'name' => 'Cropland', 'level' => 10],
                    ['type' => 'building', 'slug' => 'grain-mill', 'name' => 'Grain Mill', 'level' => 5],
                    ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 5],
                ],
                'bonuses_per_level' => $productionBonuses,
            ],
            'warehouse' => [
                'name' => 'Warehouse',
                'prerequisites' => [
                    ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 1],
                ],
                'storage_capacity_per_level' => $storageCapacities,
            ],
            'granary' => [
                'name' => 'Granary',
                'prerequisites' => [
                    ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 1],
                ],
                'storage_capacity_per_level' => $storageCapacities,
            ],
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function storageCapacityLevels(int $maxLevel): array
    {
        $capacities = [];

        for ($level = 1; $level <= $maxLevel; $level++) {
            $capacity = round(21.2 * (1.2 ** $level) - 13.2) * 100;
            $capacities[$level] = (int) $capacity;
        }

        return $capacities;
    }

    /**
     * @return array<int, int>
     */
    private function productionBonusLevels(int $maxLevel): array
    {
        $bonuses = [];

        for ($level = 1; $level <= $maxLevel; $level++) {
            $bonuses[$level] = $level * 5;
        }

        return $bonuses;
    }
}
