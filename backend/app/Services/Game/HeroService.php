<?php

namespace App\Services\Game;

use App\Models\Hero;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

class HeroService
{
    public function resolve(User $user): Hero
    {
        $hero = $user->hero;

        if (! $hero) {
            throw new ModelNotFoundException('Hero not found for this account.');
        }

        Gate::forUser($user)->authorize('view', $hero);

        return $hero;
    }

    public function overview(User $user, Hero $hero): array
    {
        Gate::forUser($user)->authorize('view', $hero);

        return [
            'id' => $hero->getKey(),
            'name' => $hero->name ?? __('Unnamed hero'),
            'level' => (int) ($hero->level ?? 0),
            'experience' => (int) ($hero->experience ?? 0),
            'health' => (int) ($hero->health ?? 0),
            'is_alive' => $hero->is_alive ?? true,
            'attributes' => array_merge(
                config('game.hero.base_attributes', []),
                $hero->attributes ?? []
            ),
            'equipment' => $hero->equipment ?? [],
        ];
    }
}
