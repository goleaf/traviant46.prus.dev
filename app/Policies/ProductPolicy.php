<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewCatalog($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->canViewCatalog($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageProducts($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->canManageProducts($user);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->canManageProducts($user);
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

    protected function canManageProducts(User $user): bool
    {
        return $user->hasStaffRole(
            StaffRole::Admin,
            StaffRole::ProductManager,
            StaffRole::CatalogManager,
        );
    }
}
