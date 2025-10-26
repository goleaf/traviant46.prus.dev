<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Enums\Game\BuildQueueState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class BuildQueue extends Model
{
    use HasFactory;

    protected $table = 'build_queues';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'village_id',
        'building_type',
        'target_level',
        'finishes_at',
        'state',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'finishes_at' => 'datetime',
            'state' => BuildQueueState::class,
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('state', BuildQueueState::Pending)
            ->where('finishes_at', '<=', Carbon::now());
    }

    public function scopeForShard(Builder $query, int $shardCount, int $shardIndex): Builder
    {
        $shardCount = max($shardCount, 1);

        if ($shardCount === 1) {
            return $query;
        }

        $normalisedIndex = ($shardIndex % $shardCount + $shardCount) % $shardCount;

        return $query->whereRaw('MOD(village_id, ?) = ?', [$shardCount, $normalisedIndex]);
    }

    public function markWorking(): void
    {
        $this->state = BuildQueueState::Working;
    }

    public function markDone(): void
    {
        $this->state = BuildQueueState::Done;
    }
}
