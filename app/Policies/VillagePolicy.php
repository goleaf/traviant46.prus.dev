<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SitterPermission;
use App\Models\Game\Village;
use App\Models\User;
use App\Support\Auth\SitterContext;

/**
 * Authorises interactions with a village for the owning player or an approved sitter.
 */
class VillagePolicy
{
    /**
     * Determine whether the actor may view the village overview.
     */
    public function view(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::Farm);
    }

    /**
     * Determine whether the actor may inspect village resource fields.
     */
    public function viewResources(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::Farm);
    }

    /**
     * Determine whether the actor may review the infrastructure view.
     */
    public function viewInfrastructure(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::Build);
    }

    /**
     * Determine whether the actor may open the rally point.
     */
    public function viewRallyPoint(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::SendTroops);
    }

    /**
     * Determine whether the actor may issue rally point commands.
     */
    public function manageRallyPoint(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::SendTroops);
    }

    /**
     * Determine whether the actor may move troops from the village.
     */
    public function manageTroops(User $actor, Village $village): bool
    {
        return $this->canAccessVillage($actor, $village, SitterPermission::SendTroops);
    }

    /**
     * Allow access for the owner or a sitter granted the given permission.
     */
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

    /**
     * Confirm the actor is the account owner for the provided village.
     */
    protected function ownsVillage(User $actor, Village $village): bool
    {
        return (int) $village->user_id === (int) $actor->getAuthIdentifier();
    }
}
