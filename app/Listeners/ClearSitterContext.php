<?php

namespace App\Listeners;

use App\Services\Auth\SessionContextManager;
use Illuminate\Auth\Events\Logout;

class ClearSitterContext
{
    public function __construct(private SessionContextManager $contextManager)
    {
    }

    public function handle(Logout $event): void
    {
        $this->contextManager->flush();
    }
}
