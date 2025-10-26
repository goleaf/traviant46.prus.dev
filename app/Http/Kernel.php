<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureAccountNotBanned;
use App\Http\Middleware\EnsureAccountVerified;
use App\Http\Middleware\EnsureGameIsAccessible;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Configuration\Middleware as MiddlewareConfig;

class Kernel
{
    public static function register(MiddlewareConfig $middleware): void
    {
        $middleware->alias([
            'game.maintenance' => EnsureGameIsAccessible::class,
            'game.banned' => EnsureAccountNotBanned::class,
            'game.verified' => EnsureAccountVerified::class,
            'auth.admin' => Authenticate::using('admin'),
            'auth.multihunter' => Authenticate::using('multihunter'),
        ]);

        $middleware->appendToGroup('web', [
            ContentSecurityPolicy::class,
            EnsureGameIsAccessible::class,
            EnsureAccountNotBanned::class,
            EnsureAccountVerified::class,
        ]);
    }
}
