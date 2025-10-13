<?php

use App\Http\Controllers\SitterController;
use App\Livewire\Account\BannedNotice;
use App\Livewire\Account\VerificationPrompt;
use App\Livewire\System\MaintenanceNotice;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('landing');

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
});
