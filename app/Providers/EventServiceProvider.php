<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\ClearSitterContext;
use App\Listeners\LogEmailVerified;
use App\Listeners\LogFailedLoginAttempt;
use App\Listeners\LogPasswordReset;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\RecordFailedLogin;
use App\Listeners\RegisterUserSession;
use App\Listeners\RemoveUserSession;
use App\Listeners\SyncLegacyRoleGuardsOnLogin;
use App\Listeners\SyncLegacyRoleGuardsOnLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Failed::class => [
            RecordFailedLogin::class,
            LogFailedLoginAttempt::class,
        ],
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
        PasswordReset::class => [
            LogPasswordReset::class,
        ],
        Verified::class => [
            LogEmailVerified::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
