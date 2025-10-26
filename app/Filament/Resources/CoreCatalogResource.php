<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\CoreCatalog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CoreCatalogResource
{
    public static function canViewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', CoreCatalog::class);
    }

    public static function canCreate(User $user): bool
    {
        return Gate::forUser($user)->allows('create', CoreCatalog::class);
    }

    public static function canUpdate(User $user): bool
    {
        return Gate::forUser($user)->allows('update', CoreCatalog::class);
    }
}
