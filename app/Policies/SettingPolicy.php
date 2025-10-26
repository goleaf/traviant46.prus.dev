<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageSettings($user);
    }

    public function view(User $user, Setting $setting): bool
    {
        return $this->canManageSettings($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageSettings($user);
    }

    public function update(User $user, Setting $setting): bool
    {
        return $this->canManageSettings($user);
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $this->canManageSettings($user);
    }

    protected function canManageSettings(User $user): bool
    {
        return $user->hasStaffRole(
            StaffRole::Admin,
            StaffRole::SettingsManager,
        );
    }
}
