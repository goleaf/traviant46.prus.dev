<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\LegacyRoleUserProvider;
use App\Enums\SitterPermission;
use App\Models\Alliance;
use App\Models\AllianceDiplomacy;
use App\Models\AllianceForum;
use App\Models\AlliancePost;
use App\Models\AllianceTopic;
use App\Models\CoreCatalog;
use App\Models\Customer;
use App\Models\Game\Village;
use App\Models\LegalDocument;
use App\Models\MultiAccountAlert;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SitterDelegation;
use App\Models\User;
use App\Policies\AllianceDiplomacyPolicy;
use App\Policies\AllianceForumPolicy;
use App\Policies\AlliancePolicy;
use App\Policies\AlliancePostPolicy;
use App\Policies\AllianceTopicPolicy;
use App\Policies\CoreCatalogPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\LegalPolicy;
use App\Policies\MultiAccountAlertPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SettingPolicy;
use App\Policies\SitterDelegationPolicy;
use App\Policies\VillagePolicy;
use App\Support\Auth\SitterContext;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Map models to their corresponding policy classes.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        CoreCatalog::class => CoreCatalogPolicy::class,
        Order::class => OrderPolicy::class,
        Customer::class => CustomerPolicy::class,
        LegalDocument::class => LegalPolicy::class,
        Setting::class => SettingPolicy::class,
        SitterDelegation::class => SitterDelegationPolicy::class,
        MultiAccountAlert::class => MultiAccountAlertPolicy::class,
        Village::class => VillagePolicy::class,
        Alliance::class => AlliancePolicy::class,
        AllianceForum::class => AllianceForumPolicy::class,
        AllianceTopic::class => AllianceTopicPolicy::class,
        AlliancePost::class => AlliancePostPolicy::class,
        AllianceDiplomacy::class => AllianceDiplomacyPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('alliance.has-role-badge', function (User $user, Alliance $alliance): bool {
            return $alliance->membershipFor($user) !== null;
        });

        Gate::define('alliance.moderator', function (User $user, Alliance $alliance): bool {
            $membership = $alliance->membershipFor($user);

            return $membership !== null && $membership->canModerateForums();
        });

        Gate::define('alliance.sitter-can-contribute', fn (User $user): bool => SitterContext::hasPermission($user, SitterPermission::AllianceContribute));

        Gate::define('alliance.sitter-can-post', fn (User $user): bool => SitterContext::hasPermission($user, SitterPermission::ManageMessages));

        Auth::provider('legacy_role', function ($app, array $config) {
            $model = $config['model'] ?? config('auth.providers.users.model');
            $legacyUid = (int) ($config['legacy_uid'] ?? 0);

            return new LegacyRoleUserProvider($app['hash'], $model, $legacyUid);
        });
    }
}
