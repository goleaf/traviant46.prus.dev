<?php

declare(strict_types=1);

use App\Enums\Game\AdventureStatus;
use App\Models\Game\Adventure;
use App\Models\Game\Village;
use App\Models\User;
use App\Support\ShardResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defaults to a single shard when configuration is zero', function (): void {
    config()->set('game.shards', 0);

    $resolver = app(ShardResolver::class);

    expect($resolver->total())->toBe(1)
        ->and($resolver->shards())->toBe([0])
        ->and($resolver->resolveForVillage(123))->toBe(0);
});

it('resolves shard indices for villages', function (): void {
    config()->set('game.shards', 4);

    $resolver = app(ShardResolver::class);

    expect($resolver->total())->toBe(4)
        ->and($resolver->resolveForVillage(7))->toBe(3);
});

it('applies shard constraints to queries', function (): void {
    config()->set('game.shards', 2);

    $resolver = app(ShardResolver::class);

    $user = User::factory()->create();
    $villageOne = Village::factory()->create(['user_id' => $user->id]);
    $villageTwo = Village::factory()->create(['user_id' => $user->id]);

    $adventureOne = Adventure::query()->create([
        'user_id' => $user->id,
        'village_id' => $villageOne->id,
        'status' => AdventureStatus::Pending,
        'completes_at' => now()->subMinute(),
    ]);

    $adventureTwo = Adventure::query()->create([
        'user_id' => $user->id,
        'village_id' => $villageTwo->id,
        'status' => AdventureStatus::Pending,
        'completes_at' => now()->subMinute(),
    ]);

    $firstShard = $resolver->resolveForVillage($villageOne->id);
    $secondShard = $resolver->resolveForVillage($villageTwo->id);

    expect($firstShard)->not->toEqual($secondShard);

    $ids = $resolver
        ->applyShardConstraint(Adventure::query()->orderBy('id'), $firstShard)
        ->pluck('id')
        ->all();

    expect($ids)->toContain($adventureOne->id)
        ->and($ids)->not->toContain($adventureTwo->id);
});
