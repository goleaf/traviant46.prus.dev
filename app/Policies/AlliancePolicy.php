<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SitterPermission;
use App\Models\Alliance;
use App\Models\AllianceMember;
use App\Models\User;
use App\Support\Auth\SitterContext;

class AlliancePolicy
{
    public function view(User $user, Alliance $alliance): bool
    {
        return $this->membership($user, $alliance) instanceof AllianceMember;
    }

    public function update(User $user, Alliance $alliance): bool
    {
        $membership = $this->membership($user, $alliance);

        if (! $membership instanceof AllianceMember || ! $membership->canManageProfile()) {
            return false;
        }

        return SitterContext::hasPermission($user, SitterPermission::AllianceContribute);
    }

    public function manageMembers(User $user, Alliance $alliance): bool
    {
        $membership = $this->membership($user, $alliance);

        if (! $membership instanceof AllianceMember || ! $membership->canManageMembers()) {
            return false;
        }

        return SitterContext::hasPermission($user, SitterPermission::AllianceContribute);
    }

    public function manageDiplomacy(User $user, Alliance $alliance): bool
    {
        $membership = $this->membership($user, $alliance);

        if (! $membership instanceof AllianceMember || ! $membership->canManageDiplomacy()) {
            return false;
        }

        return SitterContext::hasPermission($user, SitterPermission::AllianceContribute);
    }

    public function moderateForums(User $user, Alliance $alliance): bool
    {
        $membership = $this->membership($user, $alliance);

        if (! $membership instanceof AllianceMember || ! $membership->canModerateForums()) {
            return false;
        }

        return SitterContext::hasPermission($user, SitterPermission::ManageMessages);
    }

    public function recruit(User $user, Alliance $alliance): bool
    {
        $membership = $this->membership($user, $alliance);

        if (! $membership instanceof AllianceMember || ! $membership->canManageMembers()) {
            return false;
        }

        return SitterContext::hasPermission($user, SitterPermission::AllianceContribute);
    }

    public function post(User $user, Alliance $alliance): bool
    {
        return $this->membership($user, $alliance) instanceof AllianceMember
            && SitterContext::hasPermission($user, SitterPermission::ManageMessages);
    }

    private function membership(User $user, Alliance $alliance): ?AllianceMember
    {
        return $alliance->membershipFor($user);
    }
}
