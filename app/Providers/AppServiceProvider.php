<?php

namespace App\Providers;

use App\Services\Auth\SessionContextManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(SessionContextManager $contextManager): void
    {
        View::share('sessionContext', $contextManager->toArray());
    }
}
