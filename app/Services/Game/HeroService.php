<?php

namespace App\Services\Game;

use App\Models\Game\Hero;
use App\Models\Game\HeroAppearance;
use App\Models\Game\HeroInventory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class HeroService
{
    public function createStarterHero(int $userId, int $villageId, ?array $appearance = null): Hero
    {
        $timestamp = now()->timestamp;

        $hero = Hero::create([
            'uid' => $userId,
            'kid' => $villageId,
            'lastupdate' => $timestamp,
            'health' => 100,
            'experience' => 0,
            'level' => 1,
            'points' => 0,
            'speed' => 7,
        ]);

        $attributes = $appearance ?? $this->generateRandomAppearance(Config::get('game.hero.default_gender', 'male'));

        HeroAppearance::updateOrCreate(
            ['uid' => $userId],
            array_merge($attributes, ['lastupdate' => $timestamp])
        );

        HeroInventory::updateOrCreate(
            ['uid' => $userId],
            ['lastupdate' => $timestamp]
        );

        return $hero;
    }

    private function generateRandomAppearance(string $gender): array
    {
        $pools = [
            'headProfile' => [0, 1, 2, 3, 4, 5],
            'hairColor' => [0, 1, 2, 3],
            'hairStyle' => [0, 1, 2, 3, 4, 5],
            'ears' => [0, 1, 2],
            'eyebrow' => [0, 1, 2, 3],
            'eyes' => [0, 1, 2, 3, 4],
            'nose' => [0, 1, 2, 3],
            'mouth' => [0, 1, 2, 3],
            'beard' => $gender === 'male' ? [0, 1, 2, 3] : [0],
        ];

        $attributes = [];

        foreach ($pools as $key => $values) {
            $attributes[$key] = Arr::random($values);
        }

        $attributes['gender'] = $gender;

        return $attributes;
    }
}
