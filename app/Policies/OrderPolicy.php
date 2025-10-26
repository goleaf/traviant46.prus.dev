<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageOrders($user);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->canManageOrders($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageOrders($user);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->canManageOrders($user);
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->canManageOrders($user);
    }

    protected function canManageOrders(User $user): bool
    {
        return $user->hasStaffRole(
            StaffRole::Admin,
            StaffRole::ProductManager,
            StaffRole::OrderManager,
        );
    }
}
