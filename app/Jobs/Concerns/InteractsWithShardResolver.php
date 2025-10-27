<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Support\ShardResolver;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithShardResolver
{
    protected int $shard = 0;

    /**
     * Capture the shard index supplied by the scheduler or queue dispatcher.
     */
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

    /**
     * Retrieve the shard currently assigned to the job.
     */
    protected function shard(): int
    {
        return $this->shard;
    }

    /**
     * Resolve the shared ShardResolver from the container for reuse.
     */
    protected function shardResolver(): ShardResolver
    {
        return app(ShardResolver::class);
    }

    /**
     * Restrict a query so it only targets records that belong to the job's shard.
     */
    protected function constrainToShard(Builder $query, string $column = 'village_id'): Builder
    {
        return $this->shardResolver()->applyShardConstraint($query, $this->shard, $column);
    }
}
