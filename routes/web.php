<?php

use App\Http\Controllers\Admin\CampaignCustomerSegmentController;
use App\Http\Controllers\SitterController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ProductController;
use App\Livewire\Account\BannedNotice;
use App\Livewire\Account\VerificationPrompt;
use App\Livewire\System\MaintenanceNotice;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('landing');

/**
 * Public storefront routes expose the checkout funnel and product catalogue.
 */
Route::prefix('storefront')
    ->name('storefront.')
    ->group(function () {
        Route::get('/checkout', CheckoutController::class)->name('checkout');

        Route::get('/cart', CheckoutController::class)->name('cart');

        Route::get('/catalogue', function () {
            return redirect()->route('storefront.products.show', 'starter-pack');
        })->name('catalogue');

        Route::get('/products/{product}', ProductController::class)->name('products.show');
    });

Route::middleware('auth')->group(function () {
    Route::get('/maintenance', MaintenanceNotice::class)->name('game.maintenance');
    Route::get('/banned', BannedNotice::class)->name('game.banned');
    Route::get('/verify-account', VerificationPrompt::class)->name('game.verify');
});

Route::middleware(['auth', 'verified', 'game.verified', 'game.banned', 'game.maintenance'])->group(function () {
    Route::view('/home', 'dashboard')->name('home');

    Route::get('/sitters', [SitterController::class, 'index'])->name('sitters.index');
    Route::post('/sitters', [SitterController::class, 'store'])->name('sitters.store');
    Route::delete('/sitters/{sitter}', [SitterController::class, 'destroy'])->name('sitters.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('campaign-customer-segments', CampaignCustomerSegmentController::class)->except(['show']);
        Route::post(
            'campaign-customer-segments/{campaignCustomerSegment}/recalculate',
            [CampaignCustomerSegmentController::class, 'recalculate']
        )->name('campaign-customer-segments.recalculate');
    });
});
