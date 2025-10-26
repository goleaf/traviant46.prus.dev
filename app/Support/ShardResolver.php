<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ShardResolver
{
    public function __construct(private readonly ConfigRepository $config) {}

    public function total(): int
    {
        $configured = (int) $this->config->get('game.shards', 0);

        return max(1, $configured);
    }

    /**
     * @return list<int>
     */
    public function shards(): array
    {
        $total = $this->total();

        return $total === 1 ? [0] : range(0, $total - 1);
    }

    public function resolveForVillage(int $villageId): int
    {
        $total = $this->total();

        if ($total === 1) {
            return 0;
        }

        $remainder = $villageId % $total;

        return $remainder >= 0 ? $remainder : $remainder + $total;
    }

    public function applyShardConstraint(Builder $query, int $shard, string $column = 'village_id'): Builder
    {
        $total = $this->total();

        if ($total === 1) {
            return $query;
        }

        $this->assertValidShard($shard);

        $qualifiedColumn = $this->qualifyColumn($query, $column);

        return $query->whereRaw("({$qualifiedColumn} % ?) = ?", [$total, $shard]);
    }

    public function assertValidShard(int $shard): void
    {
        if ($shard < 0 || $shard >= $this->total()) {
            throw new InvalidArgumentException(sprintf('Shard index [%d] is out of range.', $shard));
        }
    }

    private function qualifyColumn(Builder $query, string $column): string
    {
        $model = $query->getModel();

        if ($model !== null) {
            return $model->qualifyColumn($column);
        }

        return $column;
    }
}
