<?php

namespace App\Providers;

use App\Auth\LegacyRoleUserProvider;
use App\Models\Game\Village;
use App\Models\MultiAccountAlert;
use App\Policies\MultiAccountAlertPolicy;
use App\Policies\VillagePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Village::class => VillagePolicy::class,
        MultiAccountAlert::class => MultiAccountAlertPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Auth::provider('legacy_role', function ($app, array $config) {
            $model = $config['model'] ?? config('auth.providers.users.model');
            $legacyUid = (int) ($config['legacy_uid'] ?? 0);

            return new LegacyRoleUserProvider($app['hash'], $model, $legacyUid);
        });
    }
}
