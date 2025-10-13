<?php

namespace App\Policies;

use App\Enums\SitterPermission;
use App\Models\Game\Village;
use App\Models\User;
use App\Services\Auth\SessionContextManager;

class VillagePolicy
{
    public function __construct(private SessionContextManager $contextManager)
    {
    }

    public function view(User $user, Village $village): bool
    {
        return $this->contextManager->allowsVillageAction($user, $village, SitterPermission::VIEW_VILLAGE);
    }

    public function update(User $user, Village $village): bool
    {
        return $this->contextManager->allowsVillageAction($user, $village, SitterPermission::MANAGE_VILLAGE);
    }

    public function switch(User $user, Village $village): bool
    {
        return $this->contextManager->allowsVillageAction($user, $village, SitterPermission::SWITCH_VILLAGE);
    }
}
