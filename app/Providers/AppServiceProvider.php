<?php

namespace App\Providers;

use App\Console\Commands\SecuredKeyGenerateCommand;
use App\Services\Provisioning\InfrastructureApiClient;
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
                $app['config']->get('provisioning.infrastructure', [])
            );
        });

        $this->app->singleton(KeyGenerateCommand::class, function ($app) {
            return tap(new SecuredKeyGenerateCommand(), static fn ($command) => $command->setLaravel($app));
        });

        $this->app->singleton('command.key.generate', function ($app) {
            return $app->make(KeyGenerateCommand::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
