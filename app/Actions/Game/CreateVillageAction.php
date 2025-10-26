<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Data\Game\VillageCreationData;
use App\Models\Game\TroopType;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use App\Repositories\Game\MapRepository;
use App\Repositories\Game\VillageRepository;
use App\Support\Travian\StarterVillageBlueprint;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateVillageAction
{
    /**
     * @var array<string, int>
     */
    private const TRIBE_NAME_LOOKUP = [
        'romans' => 1,
        'teutons' => 2,
        'gauls' => 3,
        'egyptians' => 6,
        'huns' => 7,
    ];

    public function __construct(
        private readonly VillageRepository $villages,
        private readonly MapRepository $maps,
    ) {}

    public function execute(int $x, int $y, User $user, World $world): VillageCreationData
    {
        return DB::transaction(function () use ($x, $y, $user, $world): VillageCreationData {
            $existingVillageCount = $this->villages->countForUser($user);
            $isFirstVillage = $existingVillageCount === 0;

            $storageMultiplier = (float) config('travian.settings.game.storage_multiplier', 1);
            $villageName = $this->deriveVillageName($user, $isFirstVillage, $x, $y);

            $claimedTile = $this->maps->claimCoordinates($world, $x, $y);
            $terrainType = $claimedTile?->fieldtype ?? 1;

            $now = CarbonImmutable::now();

            $attributes = [
                'user_id' => $user->getKey(),
                'legacy_kid' => $claimedTile?->getKey(),
                'name' => $villageName,
                'population' => 2,
                'loyalty' => 100,
                'culture_points' => 0,
                'x_coordinate' => $x,
                'y_coordinate' => $y,
                'terrain_type' => $terrainType,
                'village_category' => $isFirstVillage ? 'capital' : 'normal',
                'is_capital' => $isFirstVillage,
                'is_wonder_village' => false,
                'resource_balances' => StarterVillageBlueprint::baseResourceBalances($storageMultiplier),
                'storage' => StarterVillageBlueprint::baseStorage($storageMultiplier),
                'production' => StarterVillageBlueprint::baseProduction(),
                'defense_bonus' => [
                    'wall' => 0,
                    'artifact' => 0,
                    'hero' => 0,
                ],
                'founded_at' => $now,
                'abandoned_at' => null,
                'last_loyalty_change_at' => null,
            ];

            $village = $this->villages->create($attributes)->refresh();

            $resourceFields = $this->createResourceFields($village, $isFirstVillage);
            $infrastructure = $this->createInfrastructure($village);
            $garrison = $this->initialiseGarrison($village, $user);

            return new VillageCreationData(
                $village,
                $resourceFields,
                $infrastructure,
                $garrison,
            );
        });
    }

    private function createResourceFields(Village $village, bool $isFirstVillage): Collection
    {
        if (! Schema::hasTable('resource_fields')) {
            return collect();
        }

        $firstLevel = (int) config('travian.settings.game.firstVillageCreationFieldsLevel', 0);
        $otherLevel = (int) config('travian.settings.game.otherVillageCreationFieldsLevel', 0);

        $defaultLevel = $isFirstVillage ? $firstLevel : $otherLevel;
        $cropLevel = $isFirstVillage ? max(1, $defaultLevel) : $defaultLevel;

        $fields = StarterVillageBlueprint::resourceFieldBlueprint(
            cropLevel: $cropLevel,
            otherLevel: $defaultLevel,
        );

        return $this->villages->syncResourceFields($village, $fields);
    }

    private function createInfrastructure(Village $village): Collection
    {
        if (! Schema::hasTable('village_buildings')) {
            return collect();
        }

        $structures = StarterVillageBlueprint::infrastructure();

        return $this->villages->syncInfrastructure($village, $structures);
    }

    private function initialiseGarrison(Village $village, User $user): Collection
    {
        if (! Schema::hasTable('village_units') || ! Schema::hasTable('troop_types')) {
            return collect();
        }

        $tribeId = $this->resolveTribeId($user);

        $unitTypeIds = TroopType::query()
            ->where('tribe', $tribeId)
            ->pluck('id')
            ->all();

        return $this->villages->initialiseGarrison($village, $unitTypeIds);
    }

    private function resolveTribeId(User $user): int
    {
        $tribe = $user->getAttribute('tribe') ?? $user->getAttribute('race');

        if (is_numeric($tribe)) {
            $value = (int) $tribe;

            return $value > 0 ? $value : 1;
        }

        if (is_string($tribe)) {
            $normalized = Str::lower($tribe);

            return self::TRIBE_NAME_LOOKUP[$normalized] ?? 1;
        }

        return 1;
    }

    private function deriveVillageName(User $user, bool $isFirstVillage, int $x, int $y): string
    {
        $base = $user->username ?? $user->name ?? 'Village';
        $headline = Str::headline($base);

        if ($isFirstVillage) {
            $suffix = Str::endsWith(Str::lower($headline), 's') ? "'" : "'s";

            return $headline.$suffix.' Village';
        }

        return sprintf('%s (%d|%d)', $headline, $x, $y);
    }
}
