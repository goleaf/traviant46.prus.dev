<?php

namespace App\Policies;

use App\Models\SitterAssignment;
use App\Models\User;
use App\Models\Village;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Carbon;

class VillagePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Village $village): bool
    {
        if ((int) $village->owner_id === (int) $user->getKey()) {
            return true;
        }

        return $this->hasSitterPermission($user, $village, 'village:view');
    }

    public function manageQueue(User $user, Village $village): bool
    {
        if ((int) $village->owner_id === (int) $user->getKey()) {
            return true;
        }

        return $this->hasSitterPermission($user, $village, 'village:build');
    }

    protected function hasSitterPermission(User $user, Village $village, string $permission): bool
    {
        return SitterAssignment::query()
            ->where('account_id', $village->owner_id)
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
