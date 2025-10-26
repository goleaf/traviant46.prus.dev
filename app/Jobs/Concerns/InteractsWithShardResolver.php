<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Support\ShardResolver;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithShardResolver
{
    protected int $shard = 0;

    protected function initializeShardPartitioning(int $shard): void
    {
        $resolver = app(ShardResolver::class);

        if ($resolver->total() === 1) {
            $this->shard = 0;

            return;
        }

        $resolver->assertValidShard($shard);

        $this->shard = $shard;
    }

    protected function shard(): int
    {
        return $this->shard;
    }

    protected function shardResolver(): ShardResolver
    {
        return app(ShardResolver::class);
    }

    protected function constrainToShard(Builder $query, string $column = 'village_id'): Builder
    {
        return $this->shardResolver()->applyShardConstraint($query, $this->shard, $column);
    }
}
