<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\SitterDelegationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([
        'auth',
        'verified',
        'game.verified',
        'game.banned',
        'game.maintenance',
        'throttle:60,1',
    ])
    ->group(function (): void {
        Route::get('sitters', [SitterDelegationController::class, 'index'])->name('sitters.index');
        Route::post('sitters', [SitterDelegationController::class, 'store'])
            ->middleware('throttle:sitter-mutations')
            ->name('sitters.store');
        Route::delete('sitters/{sitterDelegation}', [SitterDelegationController::class, 'destroy'])
            ->middleware('throttle:sitter-mutations')
            ->name('sitters.destroy');
    });
