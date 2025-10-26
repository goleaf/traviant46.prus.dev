<?php

declare(strict_types=1);

use App\Models\Game\Adventure;
use App\Support\ShardResolver;

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
    config()->set('game.shards', 3);

    $resolver = app(ShardResolver::class);

    $builder = Adventure::query();

    $resolver->applyShardConstraint($builder, 2);

    $sql = $builder->toSql();

    expect($sql)->toContain('MOD(')
        ->and($sql)->toContain('village_id')
        ->and($builder->getBindings())->toBe([3, 2]);
});
