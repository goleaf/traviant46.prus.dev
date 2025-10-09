<?php

namespace App\Services\Game;

use App\Models\Alliance;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

class AllianceService
{
    public function resolve(User $user): Alliance
    {
        $alliance = $user->alliances()->with(['leader', 'members'])->first();

        if (! $alliance) {
            throw new ModelNotFoundException('Alliance not found for this account.');
        }

        Gate::forUser($user)->authorize('view', $alliance);

        return $alliance;
    }

    public function tools(User $user, Alliance $alliance): array
    {
        Gate::forUser($user)->authorize('view', $alliance);

        $members = $alliance->members
            ->map(function (User $member) {
                return [
                    'id' => $member->getKey(),
                    'username' => $member->username,
                    'name' => $member->name,
                    'role' => $member->pivot->role ?? 'member',
                    'permissions' => $member->pivot->permissions ?? [],
                ];
            })
            ->values();

        return [
            'alliance' => [
                'id' => $alliance->getKey(),
                'name' => $alliance->name,
                'tag' => $alliance->tag,
                'motd' => $alliance->motd,
                'description' => $alliance->description,
            ],
            'members' => $members,
            'diplomacy' => $alliance->diplomacy ?? [
                'confederacies' => [],
                'non_aggression_pacts' => [],
                'wars' => [],
            ],
        ];
    }
}
