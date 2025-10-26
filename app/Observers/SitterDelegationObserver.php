<?php

namespace App\Observers;

use App\Models\SitterDelegation;
use App\Services\Auth\AuthLookupCache;

class SitterDelegationObserver
{
    public function __construct(private readonly AuthLookupCache $cache)
    {
    }

    public function saved(SitterDelegation $delegation): void
    {
        $this->cache->forgetSitterPermissions((int) $delegation->owner_user_id);
    }

    public function deleted(SitterDelegation $delegation): void
    {
        $this->cache->forgetSitterPermissions((int) $delegation->owner_user_id);
    }

    public function restored(SitterDelegation $delegation): void
    {
        $this->cache->forgetSitterPermissions((int) $delegation->owner_user_id);
    }

    public function forceDeleted(SitterDelegation $delegation): void
    {
        $this->cache->forgetSitterPermissions((int) $delegation->owner_user_id);
    }
}
