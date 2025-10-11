<?php

namespace App\Providers;

use App\Listeners\ClearSitterContext;
use App\Listeners\LogSuccessfulLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            LogSuccessfulLogin::class,
        ],
        Logout::class => [
            ClearSitterContext::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
