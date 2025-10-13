<?php

namespace App\Services\Game;

use App\Models\Game\AvailableVillage;
use App\Models\Game\LegacyVillage;
use App\Models\Game\MapTile;
use App\Models\Game\SmithyLevel;
use App\Models\Game\TechTree;
use App\Models\Game\UnitStack;
use App\Models\Game\UserAccount;
use App\Models\Game\VillageFieldLayout;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class VillageSetupService
{
    public function createStartingVillage(UserAccount $user, AvailableVillage $spawn, int $race, bool $isCapital = true, int $expandedFrom = 0, bool $hasHero = false): LegacyVillage
    {
        return DB::transaction(function () use ($user, $spawn, $race, $isCapital, $expandedFrom, $hasHero) {
            $timestamp = now()->timestamp;
            $maxStorage = (int) round(800 * Config::get('game.storage.multiplier', 1));
            $minStorage = (int) ceil($maxStorage * 0.9375);

            $villageName = $this->buildDefaultVillageName($user->name ?? $user->getAttribute('name'));

            $village = LegacyVillage::updateOrCreate(
                ['kid' => $spawn->kid],
                [
                    'owner' => $user->getKey(),
                    'fieldtype' => $spawn->fieldtype,
                    'name' => $villageName,
                    'capital' => $isCapital,
                    'pop' => 0,
                    'cp' => 0,
                    'wood' => $minStorage,
                    'clay' => $minStorage,
                    'iron' => $minStorage,
                    'crop' => $minStorage,
                    'maxstore' => $maxStorage,
                    'maxcrop' => $maxStorage,
                    'last_loyalty_update' => $timestamp,
                    'lastmupdate' => $timestamp,
                    'created' => $timestamp,
                    'isWW' => false,
                    'expandedfrom' => $expandedFrom,
                    'lastVillageCheck' => $timestamp,
                ]
            );

            $this->assignFieldLayout($spawn->kid, $spawn->fieldtype);

            UnitStack::updateOrCreate(
                ['kid' => $spawn->kid],
                ['race' => $race, 'u11' => $hasHero ? 1 : 0]
            );

            TechTree::updateOrCreate(['kid' => $spawn->kid], ['kid' => $spawn->kid]);
            SmithyLevel::updateOrCreate(['kid' => $spawn->kid], ['kid' => $spawn->kid]);

            $spawn->update(['occupied' => true]);
            MapTile::whereKey($spawn->kid)->update(['occupied' => true]);

            $this->updatePopulationAndCulture($village, 2, 2, $user);

            return $village;
        });
    }

    public function updatePopulationAndCulture(LegacyVillage $village, int $population, int $culturePoints, UserAccount $user): void
    {
        $village->update([
            'pop' => $population,
            'cp' => $culturePoints,
        ]);

        $user->increment('total_villages');
        $user->increment('total_pop', $population);
        $user->increment('cp_prod', $culturePoints);
        $user->increment('profileCacheVersion');
    }

    public function assignFieldLayout(int $kid, int $fieldType): void
    {
        $layout = $this->getFieldLayoutByType($fieldType);

        $payload = ['kid' => $kid];

        foreach ($layout as $index => $type) {
            $payload['f' . ($index + 1) . 't'] = $type;
        }

        $payload['f26'] = 1;
        $payload['f26t'] = 15;

        VillageFieldLayout::updateOrCreate(['kid' => $kid], $payload);
    }

    private function getFieldLayoutByType(int $fieldType): array
    {
        $types = [
            1 => '4,4,1,4,4,2,3,4,4,3,3,4,4,1,4,2,1,2',
            2 => '3,4,1,3,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            3 => '1,4,1,3,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            4 => '1,4,1,2,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            5 => '1,4,1,3,1,2,3,4,4,3,3,4,4,1,4,2,1,2',
            6 => '4,4,1,3,4,4,4,4,4,4,4,4,4,4,4,2,4,4',
            7 => '1,4,4,1,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            8 => '3,4,4,1,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            9 => '3,4,4,1,1,2,3,4,4,3,3,4,4,1,4,2,1,2',
            10 => '3,4,1,2,2,2,3,4,4,3,3,4,4,1,4,2,1,2',
            11 => '3,1,1,3,1,4,4,3,3,2,2,3,1,4,4,2,4,4',
            12 => '1,4,1,1,2,2,3,4,4,3,3,4,4,1,4,2,1,2,1',
        ];

        $layout = Arr::get($types, $fieldType);

        if ($layout === null) {
            throw new InvalidArgumentException("Unsupported field type {$fieldType} for spawn layout.");
        }

        return array_map('intval', explode(',', $layout));
    }

    private function buildDefaultVillageName(?string $playerName): string
    {
        $name = $playerName ?: 'Player';

        return Str::of($name)->trim()->limit(30)->append("'s Village")->toString();
    }
}
