<?php

declare(strict_types=1);

namespace App\Repositories\Game;

use App\Models\Game\Enforcement;
use App\Models\Game\Movement;
use App\Models\Game\Unit;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CombatRepository
{
    /**
     * @return array{
     *     village: Village|null,
     *     groups: list<array{
     *         type: string,
     *         kid: int,
     *         race: int|null,
     *         model: Model|null,
     *         units: array<string, int>
     *     }>,
     *     owner_tribe: int|null,
     *     wall_level: int
     * }
     */
    public function loadDefenseState(int $targetKid): array
    {
        $groups = [];

        $home = Unit::query()->find($targetKid);
        if ($home !== null) {
            $groups[] = $this->makeDefenseGroup('home', $home);
        }

        /** @var Collection<int, Enforcement> $reinforcements */
        $reinforcements = Enforcement::query()->where('to_kid', $targetKid)->get();
        foreach ($reinforcements as $reinforcement) {
            $groups[] = $this->makeDefenseGroup('reinforcement', $reinforcement);
        }

        $village = $this->findVillageByLegacyKid($targetKid);
        $ownerTribe = null;

        if ($village !== null && $village->relationLoaded('owner') ? $village->owner !== null : $village->owner()->exists()) {
            $owner = $village->relationLoaded('owner') ? $village->owner : $village->owner()->first();
            $ownerTribe = $owner?->race !== null ? (int) $owner->race : null;
        } elseif ($home !== null && $home->race !== null) {
            $ownerTribe = (int) $home->race;
        }

        $wallLevel = $this->resolveWallLevel($village);
        $wallBonus = $this->resolveWallBonus($village);

        return [
            'village' => $village,
            'groups' => $groups,
            'owner_tribe' => $ownerTribe,
            'wall_level' => $wallLevel,
            'wall_bonus' => $wallBonus,
        ];
    }

    public function findVillageByLegacyKid(int $legacyKid): ?Village
    {
        return Village::query()
            ->with(['owner'])
            ->where('legacy_kid', $legacyKid)
            ->first();
    }

    public function findVillageByKid(int $kid): ?Village
    {
        return Village::query()
            ->with(['owner'])
            ->where('legacy_kid', $kid)
            ->first();
    }

    /**
     * @param array{type: string, kid: int, model: Model|null, units: array<string, int>, race: int|null, upkeep?: int} $group
     */
    public function persistDefenseGroup(array $group): void
    {
        $model = $group['model'];

        if ($model === null && $group['type'] === 'home') {
            $model = new Unit(['kid' => $group['kid'], 'race' => $group['race'] ?? 0]);
        }

        if ($model === null) {
            return;
        }

        foreach ($group['units'] as $slot => $count) {
            $model->{$slot} = max(0, (int) $count);
        }

        if ($model instanceof Enforcement) {
            $model->pop = Arr::get($group, 'upkeep', 0);
        }

        if ($model instanceof Unit && $group['race'] !== null) {
            $model->race = (int) $group['race'];
        }

        $model->save();
    }

    /**
     * @param array{type: string, model: Model|null} $group
     */
    public function deleteDefenseGroup(array $group): void
    {
        if ($group['model'] === null) {
            return;
        }

        if ($group['model'] instanceof Enforcement) {
            $group['model']->delete();
        }
    }

    /**
     * @return array{before: int, after: int}
     */
    public function reduceBuildingLevel(Village $village, int $slotNumber, int $levels): array
    {
        if ($levels <= 0) {
            return ['before' => 0, 'after' => 0];
        }

        $building = DB::table('buildings')
            ->where('village_id', $village->getKey())
            ->where('position', $slotNumber)
            ->first();

        if ($building === null) {
            return ['before' => 0, 'after' => 0];
        }

        $before = (int) $building->level;
        $after = max(0, $before - $levels);

        DB::table('buildings')
            ->where('id', $building->id)
            ->update(['level' => $after]);

        return ['before' => $before, 'after' => $after];
    }

    /**
     * @return array{before: int, after: int}
     */
    public function reduceWall(Village $village, int $levels): array
    {
        if ($levels <= 0) {
            return ['before' => 0, 'after' => 0];
        }

        $wallTypes = [31, 32, 33, 42, 43];

        $currentLevel = $village->defense_bonus['wall_level'] ?? null;
        $currentLevel = $currentLevel !== null ? (int) $currentLevel : 0;
        $newLevel = max(0, $currentLevel - $levels);

        $building = DB::table('buildings')
            ->where('village_id', $village->getKey())
            ->whereIn('building_type', $wallTypes)
            ->orderByDesc('level')
            ->first();

        if ($building !== null) {
            $buildingLevel = max(0, ((int) $building->level) - $levels);

            DB::table('buildings')
                ->where('id', $building->id)
                ->update(['level' => $buildingLevel]);
        }

        $defenseBonus = $village->defense_bonus ?? [];
        if (! is_array($defenseBonus)) {
            $defenseBonus = (array) $defenseBonus;
        }
        $defenseBonus['wall_level'] = $newLevel;
        $village->defense_bonus = $defenseBonus;
        $village->save();

        return ['before' => $currentLevel, 'after' => $newLevel];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function markMovementProcessed(Movement $movement, array $payload): void
    {
        $movement->proc = 1;

        $existingData = $this->decodeMovementData($movement->data);
        $movement->data = json_encode(array_merge($existingData, $payload), JSON_THROW_ON_ERROR);

        $movement->save();
    }

    /**
     * @param array<string, mixed>|null $data
     * @return array<string, mixed>
     */
    private function decodeMovementData(?string $data): array
    {
        if ($data === null || trim($data) === '') {
            return [];
        }

        $decoded = json_decode($data, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{
     *     type: string,
     *     kid: int,
     *     race: int|null,
     *     model: Model,
     *     units: array<string, int>
     * }
     */
    private function makeDefenseGroup(string $type, Model $model): array
    {
        $units = [];

        foreach (range(1, 11) as $slot) {
            $key = 'u'.$slot;
            $units[$key] = (int) ($model->{$key} ?? 0);
        }

        return [
            'type' => $type,
            'kid' => (int) ($model->to_kid ?? $model->kid ?? 0),
            'race' => $model->race !== null ? (int) $model->race : null,
            'model' => $model,
            'units' => $units,
        ];
    }

    private function resolveWallLevel(?Village $village): int
    {
        if ($village === null) {
            return 0;
        }

        $bonus = $village->defense_bonus;
        if (is_array($bonus) && isset($bonus['wall_level'])) {
            return (int) $bonus['wall_level'];
        }

        $wallTypes = [31, 32, 33, 42, 43];

        $level = DB::table('buildings')
            ->where('village_id', $village->getKey())
            ->whereIn('building_type', $wallTypes)
            ->max('level');

        return (int) ($level ?? 0);
    }

    private function resolveWallBonus(?Village $village): float
    {
        if ($village === null) {
            return 0.0;
        }

        $bonus = $village->defense_bonus;

        if (is_array($bonus) && isset($bonus['wall'])) {
            return (float) $bonus['wall'];
        }

        if (is_object($bonus) && isset($bonus->wall)) {
            return (float) $bonus->wall;
        }

        return 0.0;
    }
}
