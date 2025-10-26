<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MultiAccountAlert;
use App\Models\User;

class MultiAccountAlertPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin() || $user->isMultihunter()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, MultiAccountAlert $alert): bool
    {
        return false;
    }

    public function resolve(User $user, MultiAccountAlert $alert): bool
    {
        return false;
    }

    public function dismiss(User $user, MultiAccountAlert $alert): bool
    {
        return false;
    }
}
