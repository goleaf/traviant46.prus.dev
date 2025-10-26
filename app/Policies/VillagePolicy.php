<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SitterPermission;
use App\Models\Game\Village;
use App\Models\User;
use App\Support\Auth\SitterContext;

class VillagePolicy
{
    public function viewResources(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::Farm);
    }

    public function viewInfrastructure(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::Build);
    }

    protected function canAccessVillage(User $actor, Village $village, SitterPermission $permission): bool
    {
        if ($this->ownsVillage($actor, $village) === false) {
            return false;
        }

        if (! SitterContext::isActingAsSitter()) {
            return true;
        }

        return SitterContext::hasPermission($actor, $permission);
    }

    protected function ownsVillage(User $actor, Village $village): bool
    {
        return (int) $village->user_id === (int) $actor->getAuthIdentifier();
    }
}
