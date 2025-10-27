<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CampaignCustomerSegmentController;
use App\Http\Controllers\Admin\MultiAccountAlertController;
use App\Http\Controllers\Admin\UserSessionController;
use App\Http\Controllers\Frontend\OrderController as FrontendOrderController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\Security\TwoFactorController;
use App\Http\Controllers\SessionManagementController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ProductController;
use App\Livewire\Account\BannedNotice;
use App\Livewire\Account\TrustedDevices;
use App\Livewire\Account\VerificationPrompt;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\PlayerAudit as AdminPlayerAudit;
use App\Livewire\Game\Market as GameMarket;
use App\Livewire\Game\Messages as GameMessages;
use App\Livewire\Game\QuestLog as GameQuestLog;
use App\Livewire\Game\RallyPoint as GameRallyPoint;
use App\Livewire\Game\Reports as GameReports;
use App\Livewire\Game\Send as GameSend;
use App\Livewire\Game\Troops as GameTroops;
use App\Livewire\System\MaintenanceNotice;
use App\Livewire\Village\Infrastructure as VillageInfrastructure;
use App\Livewire\Village\Overview as VillageOverview;
use App\Models\Game\Village;
use App\Models\MultiAccountAlert;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController as FortifyPasswordResetLinkController;

$playerGuard = (string) (config('fortify.guard') ?: config('auth.defaults.guard', 'web') ?: 'web');

Route::model('village', Village::class);

Route::get('/', function () {
    return redirect()->route('login');
})->name('landing');

Route::post('/forgot-password', [FortifyPasswordResetLinkController::class, 'store'])
    ->middleware(["guest:{$playerGuard}", 'throttle:password-reset'])
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware(["guest:{$playerGuard}", 'throttle:password-reset'])
    ->name('password.update');

Route::prefix('storefront')->name('storefront.')->group(function () {
    Route::view('/cart', 'storefront.cart')->name('cart');
    Route::view('/catalogue', 'storefront.catalogue')->name('catalogue');
    Route::get('/checkout', CheckoutController::class)->name('checkout');
    Route::get('/products/{product}', ProductController::class)->name('products.show');
});

Route::middleware(["auth:{$playerGuard}"])->group(function (): void {
    Route::get('/maintenance', MaintenanceNotice::class)->name('game.maintenance');
    Route::get('/banned', BannedNotice::class)->name('game.banned');
    Route::get('/verify-account', VerificationPrompt::class)->name('game.verify');
    Route::get('/orders', [FrontendOrderController::class, 'index'])->name('frontend.orders.index');
    Route::get('/orders/create', [FrontendOrderController::class, 'create'])->name('frontend.orders.create');
    Route::post('/sessions/sign-out-everywhere', [SessionManagementController::class, 'destroyAll'])
        ->name('sessions.sign-out-everywhere');
    Route::delete('/impersonation', [ImpersonationController::class, 'destroy'])
        ->name('impersonation.destroy');
    Route::get('/security/two-factor', TwoFactorController::class)->name('security.two-factor');

    Route::middleware(['verified', 'game.banned', 'game.verified', 'game.maintenance', 'game.sitter', 'security.enforce-2fa'])->group(function (): void {
        Route::view('/home', 'dashboard')->name('home');
        Route::get('/villages/{village}/fields', \App\Livewire\Game\Fields::class)
            ->middleware('can:viewResources,village')
            ->name('game.villages.fields');
        Route::get('/messages', GameMessages::class)->name('game.messages');
        Route::get('/reports', GameReports::class)->name('game.reports');
        Route::get('/villages/{village}/resources', VillageOverview::class)
            ->middleware('can:viewResources,village')
            ->name('game.villages.overview');
        Route::get('/villages/{village}/infrastructure', VillageInfrastructure::class)
            ->middleware('can:viewInfrastructure,village')
            ->name('game.villages.infrastructure');
        Route::get('/villages/{village}/troops', GameTroops::class)
            ->middleware('can:manageTroops,village')
            ->name('game.villages.troops');
        Route::get('/villages/{village}/market', GameMarket::class)
            ->middleware('can:viewResources,village')
            ->name('game.villages.market');
        /**
         * Expose the quest log so players can review tutorial and daily objectives with reward details.
         */
        Route::get('/quests', GameQuestLog::class)
            ->name('game.quests.log');
        Route::get('/villages/{village}/buildings', \App\Livewire\Game\Buildings::class)
            ->middleware('can:viewInfrastructure,village')
            ->name('game.villages.buildings');
        Route::get('/villages/{village}/rally-point', GameRallyPoint::class)
            ->middleware('can:viewRallyPoint,village')
            ->name('game.villages.rally-point');
        Route::get('/villages/{village}/rally-point/send', GameSend::class)
            ->middleware('can:manageRallyPoint,village')
            ->name('game.villages.send');
        Route::get('/account/security', TrustedDevices::class)->name('account.security');
    });
});

Route::prefix('admin')->name('admin.')->middleware([
    'auth.admin',
    'log.staff.action',
    'security.enforce-2fa',
    'throttle:admin-actions',
])->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
    Route::get('/player-audit', AdminPlayerAudit::class)->name('player-audit');
    Route::resource('campaign-customer-segments', CampaignCustomerSegmentController::class)->except(['show']);
    Route::post(
        'campaign-customer-segments/{campaignCustomerSegment}/recalculate',
        [CampaignCustomerSegmentController::class, 'recalculate'],
    )->name('campaign-customer-segments.recalculate');

    Route::get('multi-account-alerts', [MultiAccountAlertController::class, 'index'])
        ->middleware('can:viewAny,'.MultiAccountAlert::class)
        ->name('multi-account-alerts.index');
    Route::get('multi-account-alerts/ip-lookup', [MultiAccountAlertController::class, 'lookup'])
        ->middleware('can:viewAny,'.MultiAccountAlert::class)
        ->name('multi-account-alerts.ip-lookup');
    Route::get('multi-account-alerts/{multi_account_alert}', [MultiAccountAlertController::class, 'show'])
        ->middleware('can:view,multi_account_alert')
        ->name('multi-account-alerts.show');
    Route::get('multi-account-alerts/{multi_account_alert}/activities', [MultiAccountAlertController::class, 'activities'])
        ->middleware('can:view,multi_account_alert')
        ->name('multi-account-alerts.activities');
    Route::post('multi-account-alerts/{multi_account_alert}/resolve', [MultiAccountAlertController::class, 'resolve'])
        ->middleware('can:resolve,multi_account_alert')
        ->name('multi-account-alerts.resolve');
    Route::post('multi-account-alerts/{multi_account_alert}/dismiss', [MultiAccountAlertController::class, 'dismiss'])
        ->middleware('can:dismiss,multi_account_alert')
        ->name('multi-account-alerts.dismiss');

    Route::get('sessions', [UserSessionController::class, 'index'])->name('sessions.index');
});

Route::prefix('multihunter')->name('multihunter.')->middleware([
    'auth.multihunter',
    'log.staff.action',
    'security.enforce-2fa',
    'throttle:admin-actions',
])->group(function () {
    //
});
