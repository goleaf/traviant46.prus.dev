<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\ClearSitterContext;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\RegisterUserSession;
use App\Listeners\RemoveUserSession;
use App\Listeners\SyncLegacyRoleGuardsOnLogin;
use App\Listeners\SyncLegacyRoleGuardsOnLogout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            LogSuccessfulLogin::class,
            RegisterUserSession::class,
            SyncLegacyRoleGuardsOnLogin::class,
        ],
        Logout::class => [
            ClearSitterContext::class,
            RemoveUserSession::class,
            SyncLegacyRoleGuardsOnLogout::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
