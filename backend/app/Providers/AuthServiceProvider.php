<?php

namespace App\Providers;

use App\Auth\LegacyRoleGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        $this->registerPolicies();

        Auth::extend('legacy-session', function ($app, string $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);

            $guard = new LegacyRoleGuard(
                $name,
                $provider,
                $app['session.store'],
                $app['request'],
                null,
                true,
                200000,
                $config['legacy_uid'] ?? null,
            );

            if (method_exists($guard, 'setCookieJar')) {
                $guard->setCookieJar($app['cookie']);
            }

            if (method_exists($guard, 'setDispatcher')) {
                $guard->setDispatcher($app['events']);
            }

            if (method_exists($guard, 'setRequest')) {
                $guard->setRequest($app['request']);
            }

            return $guard;
        });
    }
}
