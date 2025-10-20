<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageCustomers($user);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->canManageCustomers($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCustomers($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->canManageCustomers($user);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->canManageCustomers($user);
    }

    protected function canManageCustomers(User $user): bool
    {
        return $user->hasStaffRole(
            StaffRole::Admin,
            StaffRole::ProductManager,
            StaffRole::OrderManager,
            StaffRole::CustomerSupport,
        );
    }
}
