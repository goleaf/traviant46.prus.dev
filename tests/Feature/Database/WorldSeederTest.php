<?php

declare(strict_types=1);

use App\Models\Game\World;
use Database\Seeders\WorldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

it('seeds the default world with expected attributes', function (): void {
    seed(WorldSeeder::class);

    $world = World::query()->where('name', 'World #1')->first();

    expect($world)->not->toBeNull();
    expect($world->speed)->toBe(1.0);
    expect($world->features)->toBeArray()->toBeEmpty();
    expect($world->status)->toBe('active');
    expect($world->starts_at)->not->toBeNull();
});
