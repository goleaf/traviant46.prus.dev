<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class OrderResource
{
    public static function canViewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', Order::class);
    }

    public static function canCreate(User $user): bool
    {
        return Gate::forUser($user)->allows('create', Order::class);
    }

    public static function canUpdate(User $user): bool
    {
        return Gate::forUser($user)->allows('update', Order::class);
    }
}
