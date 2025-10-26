<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\SitterAssignmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([
        'auth',
        'verified',
        'game.verified',
        'game.banned',
        'game.maintenance',
    ])
    ->group(function (): void {
        Route::get('sitters', [SitterAssignmentController::class, 'index'])->name('sitters.index');
        Route::post('sitters', [SitterAssignmentController::class, 'store'])->name('sitters.store');
        Route::delete('sitters/{sitterAssignment}', [SitterAssignmentController::class, 'destroy'])->name('sitters.destroy');
    });
