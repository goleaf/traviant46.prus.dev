<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Enums\SitterPermission;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class SitterContext
{
    public static function isActingAsSitter(): bool
    {
        return (bool) Session::get('auth.acting_as_sitter', false);
    }

    public static function actingSitterId(): ?int
    {
        $identifier = Session::get('auth.sitter_id');

        return is_numeric($identifier) ? (int) $identifier : null;
    }

    public static function owner(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public static function hasPermission(User $owner, SitterPermission $permission): bool
    {
        if (! self::isActingAsSitter()) {
            return true;
        }

        $sitterId = self::actingSitterId();

        if (! $sitterId) {
            return false;
        }

        return SitterDelegation::query()
            ->forAccount($owner)
            ->forSitter($sitterId)
            ->active()
            ->first()?->allows($permission) ?? false;
    }
}
