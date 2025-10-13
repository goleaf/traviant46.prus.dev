<?php

namespace App\Policies;

use App\Models\MultiAccountAlert;
use App\Models\User;

class MultiAccountAlertPolicy
{
    public function view(User $user, MultiAccountAlert $alert): bool
    {
        return $this->canReview($user, $alert);
    }

    public function resolve(User $user, MultiAccountAlert $alert): bool
    {
        return $this->canReview($user, $alert);
    }

    protected function canReview(User $user, MultiAccountAlert $alert): bool
    {
        if ($user->isAdmin() || $user->isMultihunter()) {
            return true;
        }

        return in_array($user->getKey(), [$alert->primary_user_id, $alert->conflict_user_id], true);
    }
}
