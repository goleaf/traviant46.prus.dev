<?php

declare(strict_types=1);

use App\Livewire\Game\MapMinigrid;
use App\Models\Game\MapTile;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Schema::dropIfExists('wdata');

    Schema::create('wdata', function (Blueprint $table): void {
        $table->increments('id');
        $table->integer('x');
        $table->integer('y');
        $table->unsignedTinyInteger('fieldtype')->nullable();
        $table->unsignedTinyInteger('oasistype')->nullable();
        $table->unsignedTinyInteger('landscape')->nullable();
        $table->integer('crop_percent')->default(0);
        $table->boolean('occupied')->default(false);
        $table->tinyInteger('map')->nullable();
    });
});

it('renders surrounding tiles and exposes the send shortcut when available', function (): void {
    $user = User::factory()->create();

    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
        'x_coordinate' => 0,
        'y_coordinate' => 0,
        'legacy_kid' => 1,
        'name' => 'Capital Haven',
    ]);

    Village::factory()->create([
        'x_coordinate' => 1,
        'y_coordinate' => 0,
        'legacy_kid' => 2,
        'name' => 'Eastern Watch',
    ]);

    MapTile::query()->create([
        'x' => 0,
        'y' => 0,
        'fieldtype' => 3,
        'oasistype' => 0,
        'landscape' => 1,
        'crop_percent' => 0,
        'occupied' => true,
        'map' => 0,
    ]);

    MapTile::query()->create([
        'x' => 1,
        'y' => 0,
        'fieldtype' => 6,
        'oasistype' => 0,
        'landscape' => 1,
        'crop_percent' => 0,
        'occupied' => true,
        'map' => 0,
    ]);

    MapTile::query()->create([
        'x' => 0,
        'y' => 1,
        'fieldtype' => 0,
        'oasistype' => 3,
        'landscape' => 2,
        'crop_percent' => 0,
        'occupied' => false,
        'map' => 0,
    ]);

    Route::get('/send', fn () => 'send')->name('game.send');

    $this->actingAs($user);

    Livewire::test(MapMinigrid::class, ['village' => $village])
        ->assertSet('radius', 2)
        ->assertSee('Local map minigrid')
        ->assertSee($village->name)
        ->assertSee('Fields: 1-1-1-15')
        ->assertSee('+25% Wood')
        ->assertSee('+25% Crop')
        ->call('openSend', 1, 0)
        ->assertRedirect(route('game.send', [
            'origin' => $village->getKey(),
            'target_x' => 1,
            'target_y' => 0,
        ]));
});

it('falls back gracefully when the send route is not registered', function (): void {
    $user = User::factory()->create();

    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
        'x_coordinate' => 10,
        'y_coordinate' => -10,
        'legacy_kid' => 5,
        'name' => 'Frontier Bastion',
    ]);

    MapTile::query()->create([
        'x' => 10,
        'y' => -10,
        'fieldtype' => 4,
        'oasistype' => 0,
        'landscape' => 3,
        'crop_percent' => 0,
        'occupied' => true,
        'map' => 0,
    ]);

    MapTile::query()->create([
        'x' => 11,
        'y' => -10,
        'fieldtype' => 2,
        'oasistype' => 0,
        'landscape' => 1,
        'crop_percent' => 0,
        'occupied' => false,
        'map' => 0,
    ]);

    $this->actingAs($user);

    Livewire::test(MapMinigrid::class, ['village' => $village])
        ->assertSet('sendRouteAvailable', false)
        ->assertSee('Send unavailable')
        ->set('radius', 1)
        ->assertSet('radius', 1);
});
