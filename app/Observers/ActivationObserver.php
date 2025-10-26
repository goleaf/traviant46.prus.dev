<?php

namespace App\Observers;

use App\Models\Activation;
use App\Services\Auth\AuthLookupCache;

class ActivationObserver
{
    public function __construct(private readonly AuthLookupCache $cache)
    {
    }

    public function saved(Activation $activation): void
    {
        $this->cache->forgetActivation($activation);
    }

    public function deleted(Activation $activation): void
    {
        $this->cache->forgetActivation($activation);
    }

    public function restored(Activation $activation): void
    {
        $this->cache->forgetActivation($activation);
    }

    public function forceDeleted(Activation $activation): void
    {
        $this->cache->forgetActivation($activation);
    }
}
