<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\CoreCatalog;
use App\Models\User;

class CoreCatalogPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewCatalog($user);
    }

    public function view(User $user, CoreCatalog $catalog): bool
    {
        return $this->canViewCatalog($user);
    }

    public function create(User $user): bool
    {
        return $this->canAccessCatalog($user);
    }

    public function update(User $user, CoreCatalog $catalog): bool
    {
        return $this->canAccessCatalog($user);
    }

    public function delete(User $user, CoreCatalog $catalog): bool
    {
        return $this->canAccessCatalog($user);
    }

    protected function canViewCatalog(User $user): bool
    {
        return $user->hasStaffRole(
            StaffRole::Admin,
            StaffRole::ProductManager,
            StaffRole::CatalogManager,
            StaffRole::Viewer,
        );
    }

    protected function canAccessCatalog(User $user): bool
    {
        return $user->hasStaffRole(
            StaffRole::Admin,
            StaffRole::ProductManager,
            StaffRole::CatalogManager,
        );
    }
}
