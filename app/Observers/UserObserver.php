<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Auth\AuthLookupCache;

class UserObserver
{
    public function __construct(private readonly AuthLookupCache $cache)
    {
    }

    public function saved(User $user): void
    {
        $this->cache->forgetUser($user);
        $this->cache->forgetSitterPermissions($user->getKey());
    }

    public function deleted(User $user): void
    {
        $this->saved($user);
    }

    public function restored(User $user): void
    {
        $this->saved($user);
    }

    public function forceDeleted(User $user): void
    {
        $this->saved($user);
    }
}
