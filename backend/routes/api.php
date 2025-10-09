<?php

use App\Http\Controllers\Ajax\AllianceController;
use App\Http\Controllers\Ajax\HeroController;
use App\Http\Controllers\Ajax\VillageQueueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('villages/{village}')
        ->middleware('village.access')
        ->group(function () {
            Route::get('queue', [VillageQueueController::class, 'index'])->name('ajax.villages.queue.index');
            Route::post('queue', [VillageQueueController::class, 'store'])->name('ajax.villages.queue.store');
        });

    Route::prefix('hero')->group(function () {
        Route::get('/', [HeroController::class, 'overview'])->name('ajax.hero.overview');
        Route::get('inventory', [HeroController::class, 'inventory'])->name('ajax.hero.inventory');
        Route::get('{hero}', [HeroController::class, 'show'])->name('ajax.hero.show');
    });

    Route::prefix('alliance')->group(function () {
        Route::get('/', [AllianceController::class, 'overview'])->name('ajax.alliance.overview');
        Route::get('{alliance}', [AllianceController::class, 'show'])->name('ajax.alliance.show');
    });
});
