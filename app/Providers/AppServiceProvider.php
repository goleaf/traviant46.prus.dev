<?php

namespace App\Providers;

use App\Services\Provisioning\InfrastructureApiClient;
use App\Support\Storefront\StorefrontRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(InfrastructureApiClient::class, function ($app) {
            return new InfrastructureApiClient(
                $app['config']->get('provisioning.infrastructure', [])
            );
        });

        $this->app->singleton(StorefrontRepository::class, function ($app): StorefrontRepository {
            return new StorefrontRepository(
                $app['config']->get('storefront', [])
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
