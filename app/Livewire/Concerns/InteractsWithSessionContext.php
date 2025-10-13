<?php

namespace App\Livewire\Concerns;

use App\Services\Auth\SessionContextManager;

trait InteractsWithSessionContext
{
    public array $sessionContext = [];

    protected SessionContextManager $sessionContextManager;

    public function bootInteractsWithSessionContext(SessionContextManager $sessionContextManager): void
    {
        $this->sessionContextManager = $sessionContextManager;
        $this->sessionContext = $sessionContextManager->toArray();
    }

    public function hydrateInteractsWithSessionContext(): void
    {
        $this->sessionContext = $this->sessionContextManager->toArray();
    }
}
