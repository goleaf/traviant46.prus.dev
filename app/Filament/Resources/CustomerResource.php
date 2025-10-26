<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CustomerResource
{
    public static function canViewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', Customer::class);
    }

    public static function canUpdate(User $user): bool
    {
        return Gate::forUser($user)->allows('update', Customer::class);
    }
}
