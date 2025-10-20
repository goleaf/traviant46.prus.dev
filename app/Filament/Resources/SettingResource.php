<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class SettingResource
{
    public static function canViewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', Setting::class);
    }

    public static function canUpdate(User $user): bool
    {
        return Gate::forUser($user)->allows('update', Setting::class);
    }
}
