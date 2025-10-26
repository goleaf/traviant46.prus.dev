<?php

namespace App\Http;

use App\Http\Middleware\EnsureAccountNotBanned;
use App\Http\Middleware\EnsureAccountVerified;
use App\Http\Middleware\EnsureGameIsAccessible;
use Illuminate\Foundation\Configuration\Middleware as MiddlewareConfig;

class Kernel
{
    public static function register(MiddlewareConfig $middleware): void
    {
        $middleware->alias([
            'game.maintenance' => EnsureGameIsAccessible::class,
            'game.banned' => EnsureAccountNotBanned::class,
            'game.verified' => EnsureAccountVerified::class,
        ]);

        $middleware->appendToGroup('web', [
            EnsureGameIsAccessible::class,
            EnsureAccountNotBanned::class,
            EnsureAccountVerified::class,
        ]);
    }
}
