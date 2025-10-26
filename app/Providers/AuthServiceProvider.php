<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\LegacyRoleUserProvider;
use App\Models\CoreCatalog;
use App\Models\Customer;
use App\Models\LegalDocument;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SitterDelegation;
use App\Policies\CoreCatalogPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\LegalPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SettingPolicy;
use App\Policies\SitterDelegationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Product::class => ProductPolicy::class,
        CoreCatalog::class => CoreCatalogPolicy::class,
        Order::class => OrderPolicy::class,
        Customer::class => CustomerPolicy::class,
        LegalDocument::class => LegalPolicy::class,
        Setting::class => SettingPolicy::class,
        SitterDelegation::class => SitterDelegationPolicy::class,
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
