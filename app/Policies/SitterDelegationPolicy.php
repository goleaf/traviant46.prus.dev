<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SitterDelegation;
use App\Models\User;

class SitterDelegationPolicy
{
    public function viewAny(User $actor, ?User $owner = null): bool
    {
        return $this->canManage($actor, $owner);
    }

    public function create(User $actor, ?User $owner = null): bool
    {
        return $this->canManage($actor, $owner);
    }

    public function update(User $actor, SitterDelegation $delegation): bool
    {
        return $this->canManage($actor, $delegation->owner);
    }

    public function delete(User $actor, SitterDelegation $delegation): bool
    {
        return $this->canManage($actor, $delegation->owner);
    }

    protected function canManage(User $actor, ?User $owner = null): bool
    {
        if ($actor->isAdmin() || $actor->isMultihunter()) {
            return true;
        }

        if ($owner === null) {
            return false;
        }

        return $actor->is($owner);
    }
}
