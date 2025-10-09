<?php

namespace App\Policies;

use App\Models\Alliance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AlliancePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Alliance $alliance): bool
    {
        return $alliance->members->contains(fn (User $member) => $member->is($user));
    }

    public function manage(User $user, Alliance $alliance): bool
    {
        return $alliance->members
            ->firstWhere('id', $user->getKey())
            ?->pivot?->role === 'leader';
    }
}
