<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SitterPermission;
use App\Models\Game\Village;
use App\Models\User;
use App\Support\Auth\SitterContext;

class VillagePolicy
{
    public function view(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::Farm);
    }

    public function viewResources(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::Farm);
    }

    public function viewInfrastructure(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::Build);
    }

    public function viewRallyPoint(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::SendTroops);
    }

    public function manageRallyPoint(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::SendTroops);
    }

    protected function canAccessVillage(User $actor, Village $village, SitterPermission $permission): bool
    {
        if ($this->ownsVillage($actor, $village)) {
            return true;
        }

        if (! SitterContext::isActingAsSitter()) {
            return false;
        }

        $owner = $village->owner;

        if (! $owner instanceof User) {
            return false;
        }

        return SitterContext::hasPermission($owner, $permission);
    }

    protected function ownsVillage(User $actor, Village $village): bool
    {
        return (int) $village->user_id === (int) $actor->getAuthIdentifier();
    }
}
