<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Enums\SitterPermission;
use App\Models\SitterDelegation;
use App\Models\User;
use App\ValueObjects\CommunicationPermissionState;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Date;

final class CommunicationPermissionService
{
    private const PERMISSION_CACHE_KEY = 'communication.permission_state';

    public function __construct(private readonly AuthFactory $auth) {}

    public function forAuthenticatedPlayer(): CommunicationPermissionState
    {
        $user = $this->auth->guard()->user();

        if (! $user instanceof User) {
            return CommunicationPermissionState::deniedForSitter();
        }

        return $this->forUser($user);
    }

    public function forUser(User $user): CommunicationPermissionState
    {
        $actingAsSitter = (bool) session()->get('auth.acting_as_sitter', false);
        $sitterId = session()->get('auth.sitter_id');

        if (! $actingAsSitter) {
            return CommunicationPermissionState::owner();
        }

        if ($sitterId === null) {
            return CommunicationPermissionState::deniedForSitter();
        }

        $delegation = SitterDelegation::query()
            ->forAccount($user)
            ->forSitter((int) $sitterId)
            ->active(Date::now())
            ->first();

        if (! $delegation instanceof SitterDelegation) {
            return CommunicationPermissionState::deniedForSitter((int) $sitterId);
        }

        $canManageMessages = $delegation->allows(SitterPermission::ManageMessages);
        $canManageArchives = $delegation->allows(SitterPermission::ManageArchives);

        return new CommunicationPermissionState(
            canManageMessages: $canManageMessages,
            canManageArchives: $canManageArchives || $canManageMessages,
            actingAsSitter: true,
            actingSitterId: (int) $sitterId,
        );
    }
}
