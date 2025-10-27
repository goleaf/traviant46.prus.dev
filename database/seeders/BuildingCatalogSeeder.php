<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game\BuildingCatalog;
use App\Models\Game\BuildingType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class BuildingCatalogSeeder extends Seeder
{
    /**
     * Seed the building catalog table with configured economy structures.
     */
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
        /**
         * @var array<string, array<string, mixed>>|null $definitions
         */
        $definitions = config('building_catalog');

        if (! is_array($definitions)) {
            return collect();
        }

        return collect($definitions)->map(static function (array $definition): array {
            $definition['prerequisites'] = $definition['prerequisites'] ?? [];

            return $definition;
        });
    }
}
