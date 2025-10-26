<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\ApplySecurityHeaders;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnforceSessionTtl;
use App\Http\Middleware\EnsureAccountNotBanned;
use App\Http\Middleware\EnsureAccountVerified;
use App\Http\Middleware\EnsureGameIsAccessible;
use App\Http\Middleware\EnsurePrivilegedUsersHaveTwoFactor;
use App\Http\Middleware\EnsurePrivilegeSnapshotIsFresh;
use App\Http\Middleware\EnsureSitterSessionIsValid;
use App\Http\Middleware\InjectRequestContext;
use App\Http\Middleware\LogStaffAction;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Configuration\Middleware as MiddlewareConfig;

class Kernel
{
    public static function register(MiddlewareConfig $middleware): void
    {
        $middleware->prepend(InjectRequestContext::class);

        $middleware->alias([
            'game.maintenance' => EnsureGameIsAccessible::class,
            'game.banned' => EnsureAccountNotBanned::class,
            'game.verified' => EnsureAccountVerified::class,
            'game.sitter' => EnsureSitterSessionIsValid::class,
            'auth.admin' => Authenticate::using('admin'),
            'auth.multihunter' => Authenticate::using('multihunter'),
            'session.ttl' => EnforceSessionTtl::class,
            'log.staff.action' => LogStaffAction::class,
            'security.snapshot' => EnsurePrivilegeSnapshotIsFresh::class,
            'security.enforce-2fa' => EnsurePrivilegedUsersHaveTwoFactor::class,
            'security.headers' => ApplySecurityHeaders::class,
        ]);

        $middleware->appendToGroup('web', [
            ContentSecurityPolicy::class,
            EnsureGameIsAccessible::class,
            EnsureAccountNotBanned::class,
            EnsureAccountVerified::class,
            EnsurePrivilegeSnapshotIsFresh::class,
            EnsureSitterSessionIsValid::class,
            EnforceSessionTtl::class,
            ApplySecurityHeaders::class,
        ]);
    }
}
