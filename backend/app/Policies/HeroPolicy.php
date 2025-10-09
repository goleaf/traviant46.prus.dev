<?php

namespace App\Policies;

use App\Models\Hero;
use App\Models\SitterAssignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Carbon;

class HeroPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Hero $hero): bool
    {
        if ((int) $hero->user_id === (int) $user->getKey()) {
            return true;
        }

        return $this->hasSitterPermission($user, $hero, 'hero:view');
    }

    public function manage(User $user, Hero $hero): bool
    {
        if ((int) $hero->user_id === (int) $user->getKey()) {
            return true;
        }

        return $this->hasSitterPermission($user, $hero, 'hero:manage');
    }

    protected function hasSitterPermission(User $user, Hero $hero, string $permission): bool
    {
        return SitterAssignment::query()
            ->where('account_id', $hero->user_id)
            ->where('sitter_id', $user->getKey())
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->where(function ($query) use ($permission) {
                $query->whereNull('permissions')
                    ->orWhereJsonContains('permissions', $permission);
            })
            ->exists();
    }
}
