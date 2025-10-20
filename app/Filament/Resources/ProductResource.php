<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ProductResource
{
    public static function canViewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', Product::class);
    }

    public static function canCreate(User $user): bool
    {
        return Gate::forUser($user)->allows('create', Product::class);
    }

    public static function canUpdate(User $user): bool
    {
        return Gate::forUser($user)->allows('update', Product::class);
    }
}
