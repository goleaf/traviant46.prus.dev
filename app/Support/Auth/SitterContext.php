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
    /**
     * Cache active delegations for the current request.
     *
     * @var array<string, SitterDelegation|null>
     */
    private static array $delegationCache = [];

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
        $delegation = self::activeDelegation($owner);

        if ($delegation === null) {
            return ! self::isActingAsSitter();
        }

        return $delegation->allows($permission);
    }

    public static function activeDelegation(User $owner): ?SitterDelegation
    {
        if (! self::isActingAsSitter()) {
            return null;
        }

        $sitterId = self::actingSitterId();

        if (! $sitterId) {
            return null;
        }

        $cacheKey = sprintf('%d:%d', $owner->getKey(), $sitterId);

        if (array_key_exists($cacheKey, self::$delegationCache)) {
            return self::$delegationCache[$cacheKey];
        }

        $delegation = SitterDelegation::query()
            ->forAccount($owner)
            ->forSitter($sitterId)
            ->active()
            ->first();

        self::$delegationCache[$cacheKey] = $delegation;

        return $delegation;
    }
}
