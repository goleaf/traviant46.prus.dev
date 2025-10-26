<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TrainingQueue extends Model
{
    use HasFactory;

    protected $table = 'training_queues';

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'village_id',
        'troop_type_id',
        'count',
        'finishes_at',
        'building_ref',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'village_id' => 'integer',
        'troop_type_id' => 'integer',
        'count' => 'integer',
        'finishes_at' => 'datetime',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function troopType(): BelongsTo
    {
        return $this->belongsTo(TroopType::class, 'troop_type_id');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('finishes_at', '<=', Carbon::now());
    }

    public function scopeForShard(Builder $query, int $shardCount, int $shardIndex): Builder
    {
        $shardCount = max($shardCount, 1);

        if ($shardCount === 1) {
            return $query;
        }

        $normalisedIndex = ($shardIndex % $shardCount + $shardCount) % $shardCount;

        return $query->whereRaw('(village_id % ?) = ?', [$shardCount, $normalisedIndex]);
    }
}
