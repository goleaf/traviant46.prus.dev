<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Services\Communication\CommunicationPermissionService;
use App\ValueObjects\CommunicationPermissionState;

trait InteractsWithCommunicationPermissions
{
    private ?CommunicationPermissionState $permissionState = null;

    protected function communicationPermissions(): CommunicationPermissionState
    {
        if ($this->permissionState instanceof CommunicationPermissionState) {
            return $this->permissionState;
        }

        /** @var CommunicationPermissionService $service */
        $service = app(CommunicationPermissionService::class);

        return $this->permissionState = $service->forAuthenticatedPlayer();
    }

    protected function refreshCommunicationPermissions(): void
    {
        $this->permissionState = null;
    }
}
