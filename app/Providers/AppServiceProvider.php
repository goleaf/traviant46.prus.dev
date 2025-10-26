<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\SecuredKeyGenerateCommand;
use App\Models\Activation;
use App\Models\SitterDelegation;
use App\Models\User;
use App\Observers\ActivationObserver;
use App\Observers\SitterDelegationObserver;
use App\Observers\UserObserver;
use App\Services\Provisioning\InfrastructureApiClient;
use App\Services\Security\IpAnonymizer;
use App\Services\Security\MultiAccountRules;
use Illuminate\Foundation\Console\KeyGenerateCommand;
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
                $app['config']->get('provisioning.infrastructure', []),
            );
        });

        $this->app->singleton(KeyGenerateCommand::class, function ($app) {
            return tap(new SecuredKeyGenerateCommand, static fn ($command) => $command->setLaravel($app));
        });

        $this->app->singleton('command.key.generate', function ($app) {
            return $app->make(KeyGenerateCommand::class);
        });

        $this->app->singleton(IpAnonymizer::class, function ($app): IpAnonymizer {
            return new IpAnonymizer(
                $app['config']->get('privacy.ip', []),
            );
        });

        $this->app->singleton(MultiAccountRules::class, function ($app): MultiAccountRules {
            return new MultiAccountRules(
                $app['config']->get('multiaccount.allowlist', [
                    'ip_addresses' => [],
                    'cidr_ranges' => [],
                    'device_hashes' => [],
                ]),
                $app['config']->get('multiaccount.vpn_indicators', []),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Activation::observe(ActivationObserver::class);
        SitterDelegation::observe(SitterDelegationObserver::class);
    }
}
