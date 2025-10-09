<?php

use App\Http\Controllers\SitterController;
use App\Livewire\Alliance\Tools as AllianceToolsComponent;
use App\Livewire\Hero\Overview as HeroOverviewComponent;
use App\Livewire\Village\Overview as VillageOverviewComponent;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('landing');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/home', 'dashboard')->name('home');

    Route::get('/villages/{village?}', VillageOverviewComponent::class)
        ->middleware('village.access')
        ->name('villages.overview');

    Route::get('/hero', HeroOverviewComponent::class)->name('hero.overview');

    Route::get('/alliance/tools', AllianceToolsComponent::class)->name('alliance.tools');

    Route::get('/sitters', [SitterController::class, 'index'])->name('sitters.index');
    Route::post('/sitters', [SitterController::class, 'store'])->name('sitters.store');
    Route::delete('/sitters/{sitter}', [SitterController::class, 'destroy'])->name('sitters.destroy');
});
