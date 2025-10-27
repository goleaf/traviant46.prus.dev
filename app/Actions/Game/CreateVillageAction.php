<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Data\Game\VillageCreationData;
use App\Models\Game\BuildingType;
use App\Models\Game\ResourceField;
use App\Models\Game\TroopType;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageUnit;
use App\Models\Game\World;
use App\Models\User;
use App\Repositories\Game\MapRepository;
use App\Repositories\Game\VillageRepository;
use App\Support\Travian\StarterVillageBlueprint;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function collect;

/**
 * Handle the creation of a new village within the Travian world.
 */
class CreateVillageAction
{
    /**
     * Inject the repositories required to persist a new village and update the map state.
     */
    public function __construct(
        private VillageRepository $villageRepository,
        private MapRepository $mapRepository,
    ) {
    }

    /**
     * Execute the action and persist the new village.
     *
     * @param int $x The X coordinate of the new village.
     * @param int $y The Y coordinate of the new village.
     * @param User $user The owner who will receive the settlement.
     * @param World $world The Travian world where the village should be founded.
     */
    public function execute(int $x, int $y, User $user, World $world): VillageCreationData
    {
        return DB::transaction(function () use ($x, $y, $user, $world): VillageCreationData {
            $this->guardAgainstDuplicateSettlement($x, $y);

            $mapTile = $this->mapRepository->claimCoordinates($world, $x, $y);

            $villagesQuery = $user->villages();

            if (Schema::hasColumn('villages', 'world_id')) {
                $villagesQuery->where('world_id', $world->getKey());
            }

            $isFirstVillage = ! $villagesQuery->exists();

            $storageMultiplier = (float) config('travian.settings.game.storage_multiplier', 1);

            $baseBalances = StarterVillageBlueprint::baseResourceBalances($storageMultiplier);
            $baseStorage = StarterVillageBlueprint::baseStorage($storageMultiplier);
            $baseProduction = StarterVillageBlueprint::baseProduction();

            $firstVillageFieldLevel = (int) config('travian.settings.game.firstVillageCreationFieldsLevel', 0);
            $additionalVillageFieldLevel = (int) config('travian.settings.game.otherVillageCreationFieldsLevel', 0);

            $resourceBlueprint = $isFirstVillage
                ? StarterVillageBlueprint::resourceFieldBlueprint(max(1, $firstVillageFieldLevel), $firstVillageFieldLevel)
                : StarterVillageBlueprint::resourceFieldBlueprint($additionalVillageFieldLevel, $additionalVillageFieldLevel);

            $attributes = [
                'user_id' => $user->getKey(),
                'name' => $this->resolveVillageName($user, $isFirstVillage),
                'x_coordinate' => $x,
                'y_coordinate' => $y,
                'is_capital' => $isFirstVillage,
                'population' => 2,
            ];

            if (Schema::hasColumn('villages', 'world_id')) {
                $attributes['world_id'] = $world->getKey();
            }

            if (Schema::hasColumn('villages', 'legacy_kid')) {
                $attributes['legacy_kid'] = $mapTile?->getKey();
            }

            if (Schema::hasColumn('villages', 'terrain_type')) {
                $attributes['terrain_type'] = $mapTile?->fieldtype ?? 1;
            }

            if (Schema::hasColumn('villages', 'village_category')) {
                $attributes['village_category'] = $isFirstVillage ? 'capital' : 'normal';
            }

            if (Schema::hasColumn('villages', 'is_wonder_village')) {
                $attributes['is_wonder_village'] = false;
            }

            if (Schema::hasColumn('villages', 'loyalty')) {
                $attributes['loyalty'] = 100;
            }

            if (Schema::hasColumn('villages', 'culture_points')) {
                $attributes['culture_points'] = 0;
            }

            if (Schema::hasColumn('villages', 'resource_balances')) {
                $attributes['resource_balances'] = $baseBalances;
            }

            if (Schema::hasColumn('villages', 'storage')) {
                $attributes['storage'] = $baseStorage;
            }

            if (Schema::hasColumn('villages', 'production')) {
                $attributes['production'] = $baseProduction;
            }

            if (Schema::hasColumn('villages', 'founded_at')) {
                $attributes['founded_at'] = Carbon::now();
            }

            $village = Village::query()->create($attributes);

            $resourceFields = $this->seedResourceFields($village, $resourceBlueprint);
            $infrastructure = $this->seedInfrastructure($village);
            $garrison = $this->seedInitialGarrison($village, $user);

            return new VillageCreationData(
                $village->fresh(),
                $resourceFields,
                $infrastructure,
                $garrison,
            );
        });
    }

    /**
     * Ensure no existing village occupies the requested coordinates.
     */
    private function guardAgainstDuplicateSettlement(int $x, int $y): void
    {
        if ($this->villageRepository->findByCoordinates($x, $y) !== null) {
            throw new DomainException('A village already exists at the requested coordinates.');
        }
    }

    /**
     * Generate a human-friendly village name for the owner.
     */
    private function resolveVillageName(User $user, bool $isFirstVillage): string
    {
        $username = $user->username ?? $user->name ?? 'Traveller';

        return $isFirstVillage
            ? sprintf("%s's Capital", $username)
            : sprintf('%s Settlement', $username);
    }

    /**
     * Seed starter resource fields based on the blueprint definition.
     *
     * @param list<array{slot: int, kind: string, level: int}> $resourceBlueprint
     *
     * @return Collection<int, ResourceField>
     */
    private function seedResourceFields(Village $village, array $resourceBlueprint): Collection
    {
        return collect($resourceBlueprint)->map(function (array $field) use ($village): ResourceField {
            return ResourceField::query()->create([
                'village_id' => $village->getKey(),
                'slot_number' => $field['slot'],
                'kind' => $field['kind'],
                'level' => $field['level'],
                'production_per_hour_cached' => 0,
            ]);
        });
    }

    /**
     * Seed the basic infrastructure (main building, warehouse, granary, rally point).
     *
     * @return Collection<int, VillageBuilding>
     */
    private function seedInfrastructure(Village $village): Collection
    {
        $structures = StarterVillageBlueprint::infrastructure();
        $buildingTypes = BuildingType::query()
            ->whereIn('gid', collect($structures)->pluck('gid')->all())
            ->get()
            ->keyBy('gid');

        return collect($structures)->map(function (array $structure) use ($village, $buildingTypes): VillageBuilding {
            /** @var BuildingType|null $buildingType */
            $buildingType = $buildingTypes->get($structure['gid']);

            return VillageBuilding::query()->create([
                'village_id' => $village->getKey(),
                'slot_number' => $structure['slot'],
                'building_type' => $structure['gid'],
                'buildable_type' => $buildingType?->getMorphClass(),
                'buildable_id' => $buildingType?->getKey(),
                'level' => $structure['level'],
            ]);
        });
    }

    /**
     * Create an empty garrison for the player's primary tribe units.
     *
     * @return Collection<int, VillageUnit>
     */
    private function seedInitialGarrison(Village $village, User $user): Collection
    {
        $tribe = $user->tribe ?? $user->race ?? 1;

        $unitTypes = TroopType::query()
            ->when($tribe !== null, fn ($query) => $query->where('tribe', (int) $tribe))
            ->orderBy('id')
            ->limit(10)
            ->get();

        if ($unitTypes->count() < 10) {
            $unitTypes = $unitTypes->concat(
                TroopType::query()
                    ->whereNotIn('id', $unitTypes->pluck('id')->all())
                    ->orderBy('id')
                    ->limit(10 - $unitTypes->count())
                    ->get()
            );
        }

        return $unitTypes->map(function (TroopType $troopType) use ($village): VillageUnit {
            return VillageUnit::query()->create([
                'village_id' => $village->getKey(),
                'unit_type_id' => $troopType->getKey(),
                'quantity' => 0,
            ]);
        });
    }
}
